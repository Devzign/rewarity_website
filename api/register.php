<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/helpers.php';

ensure_method('POST');

$payload = get_json_body();
// Password may be conditional; don't require it here
require_keys($payload, ['name', 'email', 'user_type_id']);

$name = trim((string)$payload['name']);
$email = trim((string)$payload['email']);
$password = isset($payload['password']) ? (string)$payload['password'] : '';
$userTypeId = (int)$payload['user_type_id'];
$mobile = isset($payload['mobile']) ? trim((string)$payload['mobile']) : '';
$pincode = isset($payload['pincode']) ? trim((string)$payload['pincode']) : '';
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

// Determine type to decide auth flow
$typeStmt = $conn->prepare('SELECT UPPER(typename) AS tname FROM user_type WHERE id = ? LIMIT 1');
$typeStmt->bind_param('i', $userTypeId);
$typeStmt->execute();
$tres = $typeStmt->get_result();
$trow = $tres->fetch_assoc();
$typeStmt->close();
$typeCode = strtoupper((string)($trow['tname'] ?? ''));
$adminTypes = ['SUPER_ADMIN','ADMIN','EMPLOYEE'];

if (in_array($typeCode, $adminTypes, true)) {
  if (strlen($password) < 6) {
    json_response(422, ['error' => 'Password must be at least 6 characters long for this user type.']);
  }
} else {
  if ($mobile === '') {
    json_response(422, ['error' => 'Mobile is required for this user type.']);
  }
}

// Address is mandatory: require Address1 and Pincode
if ($addressLine1 === '') {
  json_response(422, ['error' => 'Address line 1 is required.']);
}
if ($pincode === '') {
  json_response(422, ['error' => 'Pincode is required.']);
}

try {
  $checkType = $conn->prepare('SELECT id FROM user_type WHERE id = ? LIMIT 1');
  $checkType->bind_param('i', $userTypeId);
  $checkType->execute();
  if ($checkType->get_result()->num_rows === 0) {
    $checkType->close();
    json_response(422, ['error' => 'Invalid user type.']);
  }
  $checkType->close();

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

  // Resolve city/state from pincode where possible
  $resolvedCityId = null;
  $resolvedStateId = null;
  if ($pincode !== '') {
    $lookup = $conn->prepare('SELECT pm.CityId, s.Id AS StateId
                               FROM pincode_master pm
                               INNER JOIN city_master c ON c.Id = pm.CityId
                               LEFT JOIN state_master s ON s.Id = c.StateId
                               WHERE pm.Pincode = ? LIMIT 1');
    $lookup->bind_param('s', $pincode);
    $lookup->execute();
    $res = $lookup->get_result();
    $pinRow = $res->fetch_assoc();
    $lookup->close();
    if ($pinRow) {
      $resolvedCityId = (int)$pinRow['CityId'];
      $resolvedStateId = $pinRow['StateId'] !== null ? (int)$pinRow['StateId'] : null;
      if ($cityId && $cityId !== $resolvedCityId) {
        $conn->rollback();
        json_response(422, ['error' => 'Pincode does not match selected city.']);
      }
      if ($stateId && $resolvedStateId && $stateId !== $resolvedStateId) {
        $conn->rollback();
        json_response(422, ['error' => 'Pincode does not match selected state.']);
      }
      $cityId = $resolvedCityId;
      if ($resolvedStateId) $stateId = $resolvedStateId;
    }
  }

  $userId = next_numeric_id($conn, 'user_master');
  $addressId = null;

  if (true) { // address mandatory block
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

  // Maintain pincode master mapping if provided but not found earlier, and city is known
  if ($pincode !== '') {
    $pinCheck = $conn->prepare('SELECT Id FROM pincode_master WHERE Pincode = ? LIMIT 1');
    $pinCheck->bind_param('s', $pincode);
    $pinCheck->execute();
    $pinRes = $pinCheck->get_result();
    $hasPin = (bool)$pinRes->fetch_assoc();
    $pinCheck->close();
    if (!$hasPin) {
      if (!$cityId) {
        $conn->rollback();
        json_response(422, ['error' => 'Unknown pincode. Please select city for this pincode.']);
      }
      $pinId = next_numeric_id($conn, 'pincode_master');
      $insPin = $conn->prepare('INSERT INTO pincode_master (Id, Pincode, CityId) VALUES (?, ?, ?)');
      $insPin->bind_param('isi', $pinId, $pincode, $cityId);
      $insPin->execute();
      $insPin->close();
    }
  }

  $passwordHash = $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : null;
  // Admin/staff active immediately; others verify via mobile OTP
  $isActive = in_array($typeCode, $adminTypes, true) ? 1 : 0;
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

  // Send verification OTP based on type
  if (!in_array($typeCode, $adminTypes, true)) {
    create_and_send_mobile_otp($conn, $userId, $mobile);
  }

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

  $resp = [
    'user_id' => $userId,
  ];
  if (in_array($typeCode, $adminTypes, true)) {
    $resp['message'] = 'User registered successfully. Account is active.';
  } else {
    $resp['message'] = 'User registered successfully. Please verify the OTP sent to your mobile to activate the account.';
    $resp['mobile_verification'] = 'sent';
  }
  json_response(201, $resp);
} catch (mysqli_sql_exception $exception) {
  $conn->rollback();
  json_response(500, [
    'error' => 'Failed to register user.',
    'details' => $exception->getMessage()
  ]);
}
