<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
  http_response_code(204);
  exit;
}

/**
 * Send a JSON response and terminate execution.
 */
function json_response(int $statusCode, array $payload): void
{
  http_response_code($statusCode);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
  exit;
}

// Start session for optional session-based access (admin area)
if (session_status() === PHP_SESSION_NONE) {
  @session_start();
}

/**
 * Resolve the Authorization header from various server environments.
 */
function get_authorization_header(): ?string
{
  if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
    return $_SERVER['HTTP_AUTHORIZATION'];
  }
  if (!empty($_SERVER['Authorization'])) { // Nginx/FastCGI
    return $_SERVER['Authorization'];
  }
  if (function_exists('apache_request_headers')) {
    $headers = apache_request_headers();
    foreach ($headers as $key => $value) {
      if (strtolower($key) === 'authorization') {
        return $value;
      }
    }
  }
  return null;
}

/**
 * Extract Bearer token from Authorization header.
 */
function get_bearer_token(): ?string
{
  $auth = get_authorization_header();
  if (!$auth) return null;
  if (stripos($auth, 'Bearer ') === 0) {
    return trim(substr($auth, 7));
  }
  return null;
}

/**
 * Validate incoming auth: allow valid Bearer token OR an active admin session.
 * Token is read from env var API_BEARER_TOKEN (fallback: 'dev-token').
 */
function require_auth(): void
{
  // Skip preflight
  if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    return;
  }

  // Allow if admin session present (back-office UI)
  if (!empty($_SESSION['admin_id'])) {
    return;
  }

  $expected = getenv('API_BEARER_TOKEN') ?: 'dev-token';
  $provided = get_bearer_token();
  if ($provided && hash_equals($expected, $provided)) {
    return;
  }

  json_response(401, ['error' => 'Unauthorized', 'message' => 'Missing or invalid Bearer token']);
}

/**
 * Decode JSON request payload.
 */
function get_json_body(): array
{
  $raw = file_get_contents('php://input');
  $data = json_decode($raw, true);

  if (!is_array($data)) {
    json_response(400, ['error' => 'Invalid or missing JSON payload']);
  }

  return $data;
}

/**
 * Ensure the request method is allowed.
 */
function ensure_method(string $expected): void
{
  if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== strtoupper($expected)) {
    json_response(405, ['error' => 'Method not allowed']);
  }
}

/**
 * Ensure required keys exist in the payload.
 */
function require_keys(array $input, array $keys): void
{
  $missing = [];
  foreach ($keys as $key) {
    if (!array_key_exists($key, $input)) {
      $missing[] = $key;
    }
  }

  if (!empty($missing)) {
    json_response(422, ['error' => 'Missing required fields', 'fields' => $missing]);
  }
}

/**
 * Fetch the next numeric identifier for tables that do not auto-increment.
 */
function next_numeric_id(mysqli $conn, string $table, string $column = 'Id'): int
{
  $stmt = $conn->prepare(sprintf('SELECT COALESCE(MAX(`%s`), 0) + 1 AS next_id FROM `%s`', $column, $table));
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  $stmt->close();

  return (int)($row['next_id'] ?? 1);
}

/**
 * Convert typical truthy user input into boolean.
 */
function to_bool($value): bool
{
  if (is_bool($value)) {
    return $value;
  }

  if (is_numeric($value)) {
    return (int)$value === 1;
  }

  if (is_string($value)) {
    return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
  }

  return false;
}

/**
 * Send a plain-text email. Uses PHP mail() by default.
 * In environments without a mail transfer agent, set MAIL_MOCK=1 to log to storage/mail.log instead.
 */
function send_mail(string $to, string $subject, string $body): bool
{
  $from = getenv('MAIL_FROM') ?: 'no-reply@localhost';
  $headers = [
    'From: ' . $from,
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=utf-8'
  ];

  if (to_bool(getenv('MAIL_MOCK') ?: '0')) {
    $logDir = __DIR__ . '/../storage';
    if (!is_dir($logDir)) @mkdir($logDir, 0775, true);
    $entry = sprintf("[%s] TO:%s\nSUBJECT:%s\n%s\n\n", date('c'), $to, $subject, $body);
    @file_put_contents($logDir . '/mail.log', $entry, FILE_APPEND);
    return true;
  }

  return @mail($to, $subject, $body, implode("\r\n", $headers));
}

/**
 * Ensure OTP table exists.
 */
function ensure_email_verification_table(mysqli $conn): void
{
  $sql = 'CREATE TABLE IF NOT EXISTS email_verification (
            Id INT NOT NULL AUTO_INCREMENT,
            UserId INT NOT NULL,
            Email VARCHAR(255) NOT NULL,
            Otp VARCHAR(8) NOT NULL,
            ExpiresAt DATETIME NOT NULL,
            VerifiedAt DATETIME NULL,
            CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (Id),
            INDEX (Email),
            INDEX (UserId)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
  $conn->query($sql);
}

/**
 * Create and email an OTP for the given user.
 */
function create_and_send_email_otp(mysqli $conn, int $userId, string $email): void
{
  ensure_email_verification_table($conn);
  $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
  $ttlMinutes = (int)(getenv('OTP_EXP_MINUTES') ?: 10);
  $expires = (new DateTimeImmutable("+{$ttlMinutes} minutes"))->format('Y-m-d H:i:s');

  $stmt = $conn->prepare('INSERT INTO email_verification (UserId, Email, Otp, ExpiresAt) VALUES (?, ?, ?, ?)');
  $stmt->bind_param('isss', $userId, $email, $otp, $expires);
  $stmt->execute();
  $stmt->close();

  $appName = getenv('APP_NAME') ?: 'Rewarity';
  $subject = "$appName – Verify your email";
  $body = "Your verification code is: {$otp}\n\nThis code expires in {$ttlMinutes} minutes.";
  send_mail($email, $subject, $body);
}

/**
 * Send a mock SMS (logs to storage/sms.log by default).
 * Replace with real SMS provider integration as needed.
 */
function send_sms(string $mobile, string $message): bool
{
  // Mock: always log
  $logDir = __DIR__ . '/../storage';
  if (!is_dir($logDir)) @mkdir($logDir, 0775, true);
  $entry = sprintf("[%s] TO:%s\n%s\n\n", date('c'), $mobile, $message);
  @file_put_contents($logDir . '/sms.log', $entry, FILE_APPEND);
  return true;
}

/**
 * Ensure mobile OTP table exists.
 */
function ensure_mobile_verification_table(mysqli $conn): void
{
  $sql = 'CREATE TABLE IF NOT EXISTS mobile_verification (
            Id INT NOT NULL AUTO_INCREMENT,
            UserId INT NOT NULL,
            Mobile VARCHAR(32) NOT NULL,
            Otp VARCHAR(8) NOT NULL,
            ExpiresAt DATETIME NOT NULL,
            VerifiedAt DATETIME NULL,
            CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (Id),
            INDEX (Mobile),
            INDEX (UserId)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
  $conn->query($sql);
}

/**
 * Create and SMS an OTP for the given user/mobile.
 */
function create_and_send_mobile_otp(mysqli $conn, int $userId, string $mobile): void
{
  ensure_mobile_verification_table($conn);
  $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
  $ttlMinutes = (int)(getenv('OTP_EXP_MINUTES') ?: 10);
  $expires = (new DateTimeImmutable("+{$ttlMinutes} minutes"))->format('Y-m-d H:i:s');

  $stmt = $conn->prepare('INSERT INTO mobile_verification (UserId, Mobile, Otp, ExpiresAt) VALUES (?, ?, ?, ?)');
  $stmt->bind_param('isss', $userId, $mobile, $otp, $expires);
  $stmt->execute();
  $stmt->close();

  $appName = getenv('APP_NAME') ?: 'Rewarity';
  $sms = "{$appName} code: {$otp}. Expires in {$ttlMinutes} min.";
  send_sms($mobile, $sms);
}
