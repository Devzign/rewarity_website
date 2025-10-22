<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');

ensure_method('POST');

$payload = get_json_body();
require_keys($payload, ['email', 'otp']);

$email = trim((string)$payload['email']);
$otp = trim((string)$payload['otp']);

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  json_response(422, ['error' => 'A valid email address is required.']);
}
if ($otp === '' || !preg_match('/^[0-9]{4,8}$/', $otp)) {
  json_response(422, ['error' => 'A valid OTP is required.']);
}

try {
  ensure_email_verification_table($conn);

  // Find most recent, unverified OTP that matches and is not expired
  $stmt = $conn->prepare('SELECT Id, UserId, ExpiresAt, VerifiedAt FROM email_verification WHERE Email = ? AND Otp = ? ORDER BY Id DESC LIMIT 1');
  $stmt->bind_param('ss', $email, $otp);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res->fetch_assoc();
  $stmt->close();

  if (!$row) {
    json_response(401, ['error' => 'Invalid code.']);
  }
  if (!empty($row['VerifiedAt'])) {
    json_response(409, ['error' => 'Code already used.']);
  }
  if (strtotime((string)$row['ExpiresAt']) < time()) {
    json_response(410, ['error' => 'Code expired.']);
  }

  $userId = (int)$row['UserId'];

  // Mark verified
  $now = date('Y-m-d H:i:s');
  $upd = $conn->prepare('UPDATE email_verification SET VerifiedAt = ? WHERE Id = ?');
  $upd->bind_param('si', $now, $row['Id']);
  $upd->execute();
  $upd->close();

  // Activate user
  $u = $conn->prepare('UPDATE user_master SET IsActive = 1 WHERE Id = ?');
  $u->bind_param('i', $userId);
  $u->execute();
  $u->close();

  json_response(200, ['message' => 'Email verified successfully. Your account is now active.']);
} catch (mysqli_sql_exception $e) {
  json_response(500, ['error' => 'Failed to verify email.', 'details' => $e->getMessage()]);
}

