<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');

ensure_method('POST');

$payload = get_json_body();
require_keys($payload, ['email']);

$email = trim((string)$payload['email']);
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  json_response(422, ['error' => 'A valid email address is required.']);
}

try {
  // Find the user by email
  $stmt = $conn->prepare('SELECT u.Id, u.IsActive, ut.typename AS TypeName FROM user_master u LEFT JOIN user_type ut ON ut.id = u.UserTypeId WHERE u.Email = ? LIMIT 1');
  $stmt->bind_param('s', $email);
  $stmt->execute();
  $res = $stmt->get_result();
  $user = $res->fetch_assoc();
  $stmt->close();

  if (!$user) {
    // Do not leak existence; return generic message
    json_response(200, ['message' => 'If the email exists, an OTP has been sent.']);
  }

  $typeCode = strtoupper((string)($user['TypeName'] ?? ''));
  // Limit to Dealers for now
  if ($typeCode !== 'DEALER') {
    json_response(403, ['error' => 'Email OTP login is allowed for Dealers only.']);
  }

  create_and_send_email_otp($conn, (int)$user['Id'], $email);

  json_response(200, ['message' => 'If the email exists, an OTP has been sent.']);
} catch (mysqli_sql_exception $e) {
  json_response(500, ['error' => 'Failed to send OTP.', 'details' => $e->getMessage()]);
}

