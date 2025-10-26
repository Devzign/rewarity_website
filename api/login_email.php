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

  // Find user by email
  $u = $conn->prepare('SELECT u.Id, u.UserName, u.Email, u.IsActive, u.UserTypeId, ut.typename AS TypeName FROM user_master u LEFT JOIN user_type ut ON ut.id = u.UserTypeId WHERE u.Email = ? LIMIT 1');
  $u->bind_param('s', $email);
  $u->execute();
  $ures = $u->get_result();
  $user = $ures->fetch_assoc();
  $u->close();
  if (!$user) {
    json_response(401, ['error' => 'Invalid email or user not found.']);
  }

  $typeCode = strtoupper((string)($user['TypeName'] ?? ''));
  if ($typeCode !== 'DEALER') {
    json_response(403, ['error' => 'Use email/password login for this user type.']);
  }

  // Check OTP for this email
  $stmt = $conn->prepare('SELECT Id, ExpiresAt, VerifiedAt FROM email_verification WHERE Email = ? AND Otp = ? ORDER BY Id DESC LIMIT 1');
  $stmt->bind_param('ss', $email, $otp);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res->fetch_assoc();
  $stmt->close();
  if (!$row) {
    json_response(401, ['error' => 'Invalid code.']);
  }
  if (strtotime((string)$row['ExpiresAt']) < time()) {
    json_response(410, ['error' => 'Code expired.']);
  }

  // Mark verified (allow idempotent reuse similar to mobile login)
  $now = date('Y-m-d H:i:s');
  $upd = $conn->prepare('UPDATE email_verification SET VerifiedAt = ? WHERE Id = ?');
  $upd->bind_param('si', $now, $row['Id']);
  $upd->execute();
  $upd->close();

  // Ensure user is active
  if ((int)$user['IsActive'] !== 1) {
    $au = $conn->prepare('UPDATE user_master SET IsActive = 1 WHERE Id = ?');
    $uid = (int)$user['Id'];
    $au->bind_param('i', $uid);
    $au->execute();
    $au->close();
    $user['IsActive'] = 1;
  }

  json_response(200, [
    'message' => 'Login successful.',
    'user' => [
      'id' => (int)$user['Id'],
      'name' => $user['UserName'],
      'email' => $user['Email'],
      'user_type_id' => $user['UserTypeId'] !== null ? (int)$user['UserTypeId'] : null,
      'user_type_name' => $user['TypeName'],
      'is_active' => ((int)$user['IsActive'] === 1),
    ]
  ]);
} catch (mysqli_sql_exception $e) {
  json_response(500, ['error' => 'Failed to process login.', 'details' => $e->getMessage()]);
}

