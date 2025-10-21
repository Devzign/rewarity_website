<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/helpers.php';

ensure_method('POST');

$payload = get_json_body();
require_keys($payload, ['name', 'email', 'password', 'user_type_id']);

$name = trim((string)$payload['name']);
$email = trim((string)$payload['email']);
$password = (string)$payload['password'];
$userTypeId = (int)$payload['user_type_id'];
$mobile = isset($payload['mobile']) ? trim((string)$payload['mobile']) : '';
$isActiveInput = $payload['is_active'] ?? true;

$addressLine1 = isset($payload['address_line1']) ? trim((string)$payload['address_line1']) : '';
$addressLine2 = isset($payload['address_line2']) ? trim((string)$payload['address_line2']) : '';
$cityId = isset($payload['city_id']) ? (int)$payload['city_id'] : null;
$stateId = isset($payload['state_id']) ? (int)$payload['state_id'] : null;
$countryId = isset($payload['country_id']) ? (int)$payload['country_id'] : null;

if ($name === '') {
  json_response(422, ['error' => 'Name is required.']);
}

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  json_response(422, ['error' => 'A valid email address is required.']);
}

if (strlen($password) < 6) {
  json_response(422, ['error' => 'Password must be at least 6 characters long.']);
}

try {
  $typeStmt = $conn->prepare('SELECT id FROM user_type WHERE id = ? LIMIT 1');
  $typeStmt->bind_param('i', $userTypeId);
  $typeStmt->execute();
  if ($typeStmt->get_result()->num_rows === 0) {
    $typeStmt->close();
    json_response(422, ['error' => 'Invalid user type.']);
  }
  $typeStmt->close();

  $emailCheck = $conn->prepare('SELECT Id FROM user_master WHERE Email = ? LIMIT 1');
  $emailCheck->bind_param('s', $email);
  $emailCheck->execute();
  if ($emailCheck->get_result()->num_rows > 0) {
    $emailCheck->close();
    json_response(409, ['error' => 'Email address is already registered.']);
  }
  $emailCheck->close();

  if ($mobile !== '') {
    $phoneCheck = $conn->prepare('SELECT Id FROM mobile_master WHERE MobileNumber = ? LIMIT 1');
    $phoneCheck->bind_param('s', $mobile);
    $phoneCheck->execute();
    if ($phoneCheck->get_result()->num_rows > 0) {
      $phoneCheck->close();
      json_response(409, ['error' => 'Mobile number is already registered.']);
    }
    $phoneCheck->close();
  }

  $conn->begin_transaction();

  $userId = next_numeric_id($conn, 'user_master');
  $addressId = null;

  if ($addressLine1 !== '' || $addressLine2 !== '' || $cityId || $stateId || $countryId) {
    $addressId = next_numeric_id($conn, 'address_master');
    $addressStmt = $conn->prepare(
      'INSERT INTO address_master (Id, Address1, Address2, CityId, StateId, CountryId)
       VALUES (?, ?, ?, ?, ?, ?)'
    );
    $addressStmt->bind_param(
      'issiii',
      $addressId,
      $addressLine1 !== '' ? $addressLine1 : null,
      $addressLine2 !== '' ? $addressLine2 : null,
      $cityId,
      $stateId,
      $countryId
    );
    $addressStmt->execute();
    $addressStmt->close();
  }

  $passwordHash = password_hash($password, PASSWORD_DEFAULT);
  $isActive = to_bool($isActiveInput) ? 1 : 0;
  $employeeId = null;

  $userStmt = $conn->prepare(
    'INSERT INTO user_master (Id, UserName, IsActive, Email, PasswordHash, UserTypeId, AddressId, EmployeeId)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
  );
  $userStmt->bind_param(
    'isissiii',
    $userId,
    $name,
    $isActive,
    $email,
    $passwordHash,
    $userTypeId,
    $addressId,
    $employeeId
  );
  $userStmt->execute();
  $userStmt->close();

  if ($mobile !== '') {
    $mobileId = next_numeric_id($conn, 'mobile_master');
    $isPrimary = 1;
    $mobileActive = 1;
    $mobileStmt = $conn->prepare(
      'INSERT INTO mobile_master (Id, MobileNumber, IsPrimary, IsActive, UserId)
       VALUES (?, ?, ?, ?, ?)'
    );
    $mobileStmt->bind_param('isiii', $mobileId, $mobile, $isPrimary, $mobileActive, $userId);
    $mobileStmt->execute();
    $mobileStmt->close();
  }

  $conn->commit();

  json_response(201, [
    'message' => 'User registered successfully.',
    'user_id' => $userId
  ]);
} catch (mysqli_sql_exception $exception) {
  $conn->rollback();
  json_response(500, [
    'error' => 'Failed to register user.',
    'details' => $exception->getMessage()
  ]);
}
