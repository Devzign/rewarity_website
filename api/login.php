<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/helpers.php';

ensure_method('POST');

$payload = get_json_body();
require_keys($payload, ['email', 'password']);

$email = trim((string)$payload['email']);
$password = (string)$payload['password'];

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  json_response(422, ['error' => 'A valid email address is required.']);
}

if ($password === '') {
  json_response(422, ['error' => 'Password is required.']);
}

try {
  $stmt = $conn->prepare(
    'SELECT u.Id,
            u.UserName,
            u.Email,
            u.PasswordHash,
            u.IsActive,
            u.UserTypeId,
            ut.typename AS UserType
     FROM user_master u
     LEFT JOIN user_type ut ON ut.id = u.UserTypeId
     WHERE u.Email = ?
     LIMIT 1'
  );
  $stmt->bind_param('s', $email);
  $stmt->execute();
  $result = $stmt->get_result();
  $user = $result->fetch_assoc();
  $stmt->close();

  if (!$user || empty($user['PasswordHash'])) {
    json_response(401, ['error' => 'Invalid credentials.']);
  }

  if (!password_verify($password, $user['PasswordHash'])) {
    json_response(401, ['error' => 'Invalid credentials.']);
  }

  $isActive = (int)$user['IsActive'] === 1;

  json_response(200, [
    'message' => 'Login successful.',
    'user' => [
      'id' => (int)$user['Id'],
      'name' => $user['UserName'],
      'email' => $user['Email'],
      'user_type_id' => $user['UserTypeId'] !== null ? (int)$user['UserTypeId'] : null,
      'user_type_name' => $user['UserType'],
      'is_active' => $isActive,
    ]
  ]);
} catch (mysqli_sql_exception $exception) {
  json_response(500, [
    'error' => 'Failed to process login.',
    'details' => $exception->getMessage()
  ]);
}
