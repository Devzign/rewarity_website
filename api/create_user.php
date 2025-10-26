<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/helpers.php';

ensure_method('POST');
$payload = get_json_body();

require_keys($payload, ['username']);

$username = trim((string)$payload['username']);
if ($username === '') {
  json_response(422, ['error' => 'Username cannot be empty']);
}

$email = isset($payload['email']) ? trim((string)$payload['email']) : null;
if ($email !== null && $email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  json_response(422, ['error' => 'Invalid email address']);
}

$userTypeId = isset($payload['user_type_id']) ? (int)$payload['user_type_id'] : null;
$employeeId = isset($payload['employee_id']) ? (int)$payload['employee_id'] : null;
$isActive = to_bool($payload['is_active'] ?? true) ? 1 : 0;

$addressData = isset($payload['address']) && is_array($payload['address'])
  ? $payload['address']
  : null;

try {
  $conn->begin_transaction();

  $addressId = null;
  if ($addressData !== null) {
    $addressId = next_numeric_id($conn, 'address_master');

    $address1 = isset($addressData['address1']) ? trim((string)$addressData['address1']) : null;
    $address2 = isset($addressData['address2']) ? trim((string)$addressData['address2']) : null;
    $cityId = isset($addressData['city_id']) ? (int)$addressData['city_id'] : null;
    $stateId = isset($addressData['state_id']) ? (int)$addressData['state_id'] : null;
    $countryId = isset($addressData['country_id']) ? (int)$addressData['country_id'] : null;
    // Optional coordinates
    $latitude = isset($addressData['latitude']) && $addressData['latitude'] !== '' ? (float)$addressData['latitude'] : null;
    $longitude = isset($addressData['longitude']) && $addressData['longitude'] !== '' ? (float)$addressData['longitude'] : null;
    // Detect optional Latitude/Longitude columns on address_master
    $hasLat = false; $hasLng = false;
    try {
      $colLat = $conn->query("SHOW COLUMNS FROM address_master LIKE 'Latitude'");
      $hasLat = ($colLat && $colLat->num_rows > 0);
      $colLat?->free();
      $colLng = $conn->query("SHOW COLUMNS FROM address_master LIKE 'Longitude'");
      $hasLng = ($colLng && $colLng->num_rows > 0);
      $colLng?->free();
    } catch (mysqli_sql_exception $e) {
      $hasLat = $hasLng = false;
    }

    if ($hasLat || $hasLng) {
      $sql = 'INSERT INTO address_master (Id, Address1, Address2, CityId, StateId, CountryId';
      $place = '?, ?, ?, ?, ?, ?';
      $types = 'issiii';
      $sqlVals = ') VALUES (' . $place;
      if ($hasLat) { $sql .= ', Latitude'; $sqlVals .= ', ?'; $types .= 'd'; }
      if ($hasLng) { $sql .= ', Longitude'; $sqlVals .= ', ?'; $types .= 'd'; }
      $sql .= $sqlVals . ')';
      $addressStmt = $conn->prepare($sql);
      if ($hasLat && $hasLng) {
        $addressStmt->bind_param($types, $addressId, $address1, $address2, $cityId, $stateId, $countryId, $latitude, $longitude);
      } elseif ($hasLat && !$hasLng) {
        $addressStmt->bind_param($types, $addressId, $address1, $address2, $cityId, $stateId, $countryId, $latitude);
      } else { // !$hasLat && $hasLng
        $addressStmt->bind_param($types, $addressId, $address1, $address2, $cityId, $stateId, $countryId, $longitude);
      }
      $addressStmt->execute();
      $addressStmt->close();
    } else {
      $addressStmt = $conn->prepare(
        'INSERT INTO address_master (Id, Address1, Address2, CityId, StateId, CountryId)
         VALUES (?, ?, ?, ?, ?, ?)'
      );
      $addressStmt->bind_param('issiii', $addressId, $address1, $address2, $cityId, $stateId, $countryId);
      $addressStmt->execute();
      $addressStmt->close();
    }
  }

  $userId = next_numeric_id($conn, 'user_master');

  $userStmt = $conn->prepare(
    'INSERT INTO user_master (Id, UserName, IsActive, Email, UserTypeId, AddressId, EmployeeId)
     VALUES (?, ?, ?, ?, ?, ?, ?)'
  );

  $userStmt->bind_param(
    'isisiii',
    $userId,
    $username,
    $isActive,
    $email,
    $userTypeId,
    $addressId,
    $employeeId
  );

  $userStmt->execute();
  $userStmt->close();

  $conn->commit();

  json_response(201, [
    'message' => 'User created successfully',
    'user_id' => $userId,
    'address_id' => $addressId
  ]);
} catch (mysqli_sql_exception $exception) {
  $conn->rollback();

  $error = [
    'error' => 'Failed to create user',
    'details' => $exception->getMessage()
  ];

  if ($exception->getCode() === 1062) {
    $error['hint'] = 'Duplicate entry detected';
    json_response(409, $error);
  }

  json_response(500, $error);
}
