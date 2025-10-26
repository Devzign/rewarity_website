<?php
require __DIR__ . '/layout/init.php';

$currentPage = 'users';
$headerTitle = 'User Management';

$defaultUserTypes = [
  ['id' => 1, 'name' => 'SUPER_ADMIN', 'description' => 'Full access'],
  ['id' => 2, 'name' => 'EMPLOYEE', 'description' => 'Head-office staff'],
  ['id' => 3, 'name' => 'DISTRIBUTOR', 'description' => 'Regional distributor'],
  ['id' => 4, 'name' => 'DEALER', 'description' => 'Dealer / retailer'],
  ['id' => 5, 'name' => 'SALESPERSON', 'description' => 'Field salesperson'],
];

try {
  $countResult = $conn->query('SELECT COUNT(*) AS total FROM user_type');
  $totalTypes = 0;
  if ($countResult && ($row = $countResult->fetch_assoc())) {
    $totalTypes = (int)$row['total'];
  }
  $countResult?->free();

  if ($totalTypes === 0) {
    $seedStmt = $conn->prepare('INSERT INTO user_type (id, typename, description) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE typename = VALUES(typename), description = VALUES(description)');
    foreach ($defaultUserTypes as $type) {
      $seedStmt->bind_param('iss', $type['id'], $type['name'], $type['description']);
      $seedStmt->execute();
    }
    $seedStmt->close();
  }
} catch (mysqli_sql_exception $exception) {
  // Silent failure, page will show empty dropdown if user_type is inaccessible.
}

function next_numeric_id(mysqli $conn, string $table, string $column = 'Id'): int
{
  $sql = sprintf('SELECT COALESCE(MAX(`%s`), 0) + 1 AS next_id FROM `%s`', $column, $table);
  $result = $conn->query($sql);
  if (!$result) {
    throw new mysqli_sql_exception('Failed to determine next identifier.');
  }
  $row = $result->fetch_assoc();
  $result->free();
  return (int)($row['next_id'] ?? 1);
}

$filters = [
  'name' => trim((string)($_GET['name'] ?? '')),
  'mobile' => trim((string)($_GET['mobile'] ?? '')),
  'user_type' => trim((string)($_GET['user_type'] ?? '')),
  'status' => trim((string)($_GET['status'] ?? '')),
];

$successMessage = null;
$errorMessage = null;
$formErrors = [];
$formData = [
  'name' => '',
  'email' => '',
  'password' => '',
  'confirm_password' => '',
  'user_type_id' => '',
  'mobile' => '',
  'pincode' => '',
  'is_active' => '1',
  'address1' => '',
  'address2' => '',
];

if (isset($_GET['created']) && $_GET['created'] === '1') {
  $successMessage = 'User created successfully.';
}

$userTypes = [];
try {
  if ($typeResult = $conn->query('SELECT id, typename FROM user_type ORDER BY typename')) {
    while ($row = $typeResult->fetch_assoc()) {
      $userTypes[] = $row;
    }
    $typeResult->free();
  }
} catch (mysqli_sql_exception $exception) {
  $errorMessage = 'Unable to load user types: ' . $exception->getMessage();
}

// Detect the numeric id for DEALER type, if present
$dealerTypeId = null;
foreach ($userTypes as $ut) {
  if (strtoupper((string)($ut['typename'] ?? '')) === 'DEALER') {
    $dealerTypeId = (int)$ut['id'];
    break;
  }
}

/**
 * Convert canonical user type code (e.g., SUPER_ADMIN) to display label (e.g., Super Admin).
 */
function display_user_type_label(?string $code): string
{
  if ($code === null || $code === '') return '—';
  return ucwords(strtolower(str_replace('_', ' ', $code)));
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_user') {
  $formData['name'] = trim((string)($_POST['name'] ?? ''));
  $formData['email'] = trim((string)($_POST['email'] ?? ''));
  $formData['password'] = (string)($_POST['password'] ?? '');
  $formData['confirm_password'] = (string)($_POST['confirm_password'] ?? '');
  $formData['user_type_id'] = trim((string)($_POST['user_type_id'] ?? ''));
  $formData['mobile'] = trim((string)($_POST['mobile'] ?? ''));
  $formData['pincode'] = trim((string)($_POST['pincode'] ?? ''));
  $formData['is_active'] = isset($_POST['is_active']) ? '1' : '0';
  $formData['address1'] = trim((string)($_POST['address1'] ?? ''));
  $formData['address2'] = trim((string)($_POST['address2'] ?? ''));
  // Optional coordinates (used for Dealer via map picker)
  $latitude = isset($_POST['latitude']) && $_POST['latitude'] !== '' ? (float)$_POST['latitude'] : null;
  $longitude = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? (float)$_POST['longitude'] : null;
  $cityId = isset($_POST['city_id']) && $_POST['city_id'] !== '' ? (int)$_POST['city_id'] : null;
  $stateId = isset($_POST['state_id']) && $_POST['state_id'] !== '' ? (int)$_POST['state_id'] : null;
  $countryId = isset($_POST['country_id']) && $_POST['country_id'] !== '' ? (int)$_POST['country_id'] : null;

  if ($formData['name'] === '') {
    $formErrors['name'] = 'Name is required.';
  }

  if ($formData['email'] === '' || !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
    $formErrors['email'] = 'A valid email is required.';
  }

  // Validate password only when NOT creating a Dealer
  $selectedTypeId = $formData['user_type_id'] !== '' ? (int)$formData['user_type_id'] : null;
  $isDealerSelected = ($dealerTypeId !== null && $selectedTypeId === $dealerTypeId);
  if (!$isDealerSelected) {
    if ($formData['password'] === '' || strlen($formData['password']) < 6) {
      $formErrors['password'] = 'Password must be at least 6 characters long.';
    }
    if ($formData['password'] !== $formData['confirm_password']) {
      $formErrors['confirm_password'] = 'Passwords do not match.';
    }
  } else {
    // For Dealers, ignore any provided passwords
    $formData['password'] = '';
    $formData['confirm_password'] = '';
  }

  if ($formData['user_type_id'] === '') {
    $formErrors['user_type_id'] = 'Please choose a user type.';
  }

  if ($formData['mobile'] !== '' && !preg_match('/^[0-9 +()-]{6,20}$/', $formData['mobile'])) {
    $formErrors['mobile'] = 'Please enter a valid mobile number.';
  }

  $transactionStarted = false;

  try {
    if (empty($formErrors)) {
      $emailCheck = $conn->prepare('SELECT Id FROM user_master WHERE Email = ? LIMIT 1');
      $emailCheck->bind_param('s', $formData['email']);
      $emailCheck->execute();
      if ($emailCheck->get_result()->num_rows > 0) {
        $formErrors['email'] = 'This email is already registered.';
      }
      $emailCheck->close();

      if ($formData['mobile'] !== '' && empty($formErrors)) {
        $mobileCheck = $conn->prepare('SELECT Id FROM mobile_master WHERE MobileNumber = ? LIMIT 1');
        $mobileCheck->bind_param('s', $formData['mobile']);
        $mobileCheck->execute();
        if ($mobileCheck->get_result()->num_rows > 0) {
          $formErrors['mobile'] = 'This mobile number is already registered.';
        }
        $mobileCheck->close();
      }
    }

    if (empty($formErrors)) {
      $conn->begin_transaction();
      $transactionStarted = true;

      $userId = next_numeric_id($conn, 'user_master');
      $addressId = null;

      // Create address record if we have any address lines, any region IDs, or coordinates
      if ($formData['address1'] !== '' || $formData['address2'] !== '' || $cityId || $stateId || $countryId || $latitude !== null || $longitude !== null) {
        $addressId = next_numeric_id($conn, 'address_master');
        // Detect optional latitude/longitude columns in address_master
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
          // Build insert including Latitude/Longitude when columns exist
          $sql = 'INSERT INTO address_master (Id, Address1, Address2, CityId, StateId, CountryId';
          $place = '?, ?, ?, ?, ?, ?';
          $types = 'issiii';
          $params = [];
          $sqlVals = ') VALUES (' . $place;
          if ($hasLat) { $sql .= ', Latitude'; $sqlVals .= ', ?'; $types .= 'd'; }
          if ($hasLng) { $sql .= ', Longitude'; $sqlVals .= ', ?'; $types .= 'd'; }
          $sql .= $sqlVals . ')';
          $addressStmt = $conn->prepare($sql);
          // Prepare bind parameters as variables (bind_param needs references)
          $addr1Param = ($formData['address1'] !== '' ? $formData['address1'] : null);
          $addr2Param = ($formData['address2'] !== '' ? $formData['address2'] : null);
          $cityIdParam = ($cityId ?? null);
          $stateIdParam = ($stateId ?? null);
          $countryIdParam = ($countryId ?? null);
          $latParam = $latitude;
          $lngParam = $longitude;
          // Bind dynamically
          if ($hasLat && $hasLng) {
            $addressStmt->bind_param(
              $types,
              $addressId,
              $addr1Param,
              $addr2Param,
              $cityIdParam,
              $stateIdParam,
              $countryIdParam,
              $latParam,
              $lngParam
            );
          } elseif ($hasLat && !$hasLng) {
            $addressStmt->bind_param(
              $types,
              $addressId,
              $addr1Param,
              $addr2Param,
              $cityIdParam,
              $stateIdParam,
              $countryIdParam,
              $latParam
            );
          } elseif (!$hasLat && $hasLng) {
            $addressStmt->bind_param(
              $types,
              $addressId,
              $addr1Param,
              $addr2Param,
              $cityIdParam,
              $stateIdParam,
              $countryIdParam,
              $lngParam
            );
          }
        } else {
          // No lat/lng columns: fall back to original insert
          $addressStmt = $conn->prepare(
            'INSERT INTO address_master (Id, Address1, Address2, CityId, StateId, CountryId) VALUES (?, ?, ?, ?, ?, ?)'
          );
          $addr1Param = ($formData['address1'] !== '' ? $formData['address1'] : null);
          $addr2Param = ($formData['address2'] !== '' ? $formData['address2'] : null);
          $cityIdParam = ($cityId ?? null);
          $stateIdParam = ($stateId ?? null);
          $countryIdParam = ($countryId ?? null);
          $addressStmt->bind_param('issiii', $addressId, $addr1Param, $addr2Param, $cityIdParam, $stateIdParam, $countryIdParam);
        }
        $addressStmt->execute();
        $addressStmt->close();
      }

      $passwordHash = $formData['password'] !== '' ? password_hash($formData['password'], PASSWORD_DEFAULT) : null;
      $isActiveValue = $formData['is_active'] === '1' ? 1 : 0;
      $userTypeIdValue = (int)$formData['user_type_id'];
      $nameParam = $formData['name'];
      $emailParam = $formData['email'];
      $addressParam = $addressId ?: null;
      $employeeParam = null;

      // Detect if PasswordHash column exists in user_master
      $hasPasswordHash = false;
      try {
        $colChk = $conn->query("SHOW COLUMNS FROM user_master LIKE 'PasswordHash'");
        $hasPasswordHash = ($colChk && $colChk->num_rows > 0);
        $colChk?->free();
      } catch (mysqli_sql_exception $e) {
        $hasPasswordHash = false;
      }

      if ($hasPasswordHash) {
        $userStmt = $conn->prepare(
          'INSERT INTO user_master (Id, UserName, IsActive, Email, PasswordHash, UserTypeId, AddressId, EmployeeId)
           VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $userStmt->bind_param(
          'isissiii',
          $userId,
          $nameParam,
          $isActiveValue,
          $emailParam,
          $passwordHash,
          $userTypeIdValue,
          $addressParam,
          $employeeParam
        );
      } else {
        // Fallback for schemas without PasswordHash column
        $userStmt = $conn->prepare(
          'INSERT INTO user_master (Id, UserName, IsActive, Email, UserTypeId, AddressId, EmployeeId)
           VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $userStmt->bind_param(
          'isisiii',
          $userId,
          $nameParam,
          $isActiveValue,
          $emailParam,
          $userTypeIdValue,
          $addressParam,
          $employeeParam
        );
      }
      $userStmt->execute();
      $userStmt->close();

      if ($formData['mobile'] !== '') {
        $mobileId = next_numeric_id($conn, 'mobile_master');
        $isPrimary = 1;
        $mobileActive = 1;
        $mobileParam = $formData['mobile'];
        $mobileStmt = $conn->prepare(
          'INSERT INTO mobile_master (Id, MobileNumber, IsPrimary, IsActive, UserId) VALUES (?, ?, ?, ?, ?)'
        );
        $mobileStmt->bind_param('isiii', $mobileId, $mobileParam, $isPrimary, $mobileActive, $userId);
        $mobileStmt->execute();
        $mobileStmt->close();
      }

      $conn->commit();
      $transactionStarted = false;
      header('Location: /admin/users.php?created=1');
      exit;
    }
  } catch (mysqli_sql_exception $exception) {
    if ($transactionStarted) {
      $conn->rollback();
    }
    $errorMessage = 'Failed to create user: ' . $exception->getMessage();
  }
}

$users = [];
try {
  $sql = <<<SQL
SELECT
  u.Id,
  u.UserName,
  u.Email,
  u.IsActive,
  u.CreatedOn,
  ut.typename AS UserType,
  m.MobileNumber,
  a.Address1,
  a.Address2,
  c.CityName,
  s.StateName,
  co.CountryName
FROM user_master u
LEFT JOIN user_type ut ON ut.id = u.UserTypeId
LEFT JOIN (
  SELECT UserId, MobileNumber
  FROM (
    SELECT UserId,
           MobileNumber,
           ROW_NUMBER() OVER (PARTITION BY UserId ORDER BY IsPrimary DESC, Id ASC) AS rn
    FROM mobile_master
  ) ranked
  WHERE rn = 1
) m ON m.UserId = u.Id
LEFT JOIN address_master a ON a.Id = u.AddressId
LEFT JOIN city_master c ON c.Id = a.CityId
LEFT JOIN state_master s ON s.Id = a.StateId
LEFT JOIN country_master co ON co.Id = a.CountryId
SQL;

  $conditions = [];
  $types = '';
  $params = [];

  if ($filters['name'] !== '') {
    $conditions[] = 'u.UserName LIKE ?';
    $types .= 's';
    $like = '%' . $filters['name'] . '%';
    $params[] = $like;
  }

  if ($filters['mobile'] !== '') {
    $conditions[] = 'm.MobileNumber LIKE ?';
    $types .= 's';
    $likeMobile = '%' . $filters['mobile'] . '%';
    $params[] = $likeMobile;
  }

  if ($filters['user_type'] !== '') {
    $conditions[] = 'u.UserTypeId = ?';
    $types .= 'i';
    $params[] = (int)$filters['user_type'];
  }

  if ($filters['status'] === 'active') {
    $conditions[] = 'u.IsActive = 1';
  } elseif ($filters['status'] === 'inactive') {
    $conditions[] = 'u.IsActive = 0';
  }

  if (!empty($conditions)) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
  }

  $sql .= ' ORDER BY u.CreatedOn DESC, u.Id DESC LIMIT 200';
  $stmt = $conn->prepare($sql);

  if ($types !== '') {
    $bindParams = [$types];
    foreach ($params as $key => $value) {
      $bindParams[] = &$params[$key];
    }
    call_user_func_array([$stmt, 'bind_param'], $bindParams);
  }

  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    $users[] = $row;
  }
  $stmt->close();
} catch (mysqli_sql_exception $exception) {
  $errorMessage = 'Failed to load users: ' . $exception->getMessage();
}

function render_status_badge(mixed $value): string
{
  $isActive = (int)$value === 1 || $value === '1';
  $class = $isActive ? 'badge light badge-success' : 'badge light badge-danger';
  $label = $isActive ? 'Active' : 'Inactive';
  return sprintf('<span class="%s">%s</span>', $class, $label);
}

function render_address(array $row): string
{
  $parts = [];
  foreach (['Address1', 'Address2', 'CityName', 'StateName', 'CountryName'] as $key) {
    if (!empty($row[$key])) {
      $parts[] = $row[$key];
    }
  }
  return $parts ? htmlspecialchars(implode(', ', $parts)) : '—';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>User Management - Rewarity</title>
    <base href="<?php echo htmlspecialchars($assetBase, ENT_QUOTES); ?>/">
    <script>try{var t=localStorage.getItem('rewarity_theme')||'light';var a=localStorage.getItem('rewarity_accent')||'green';var h=document.documentElement;h.setAttribute('data-theme',t);h.setAttribute('data-accent',a);}catch(e){}</script>
    <link href="vendor/jquery-nice-select/css/nice-select.css" rel="stylesheet">
    <link rel="stylesheet" href="vendor/nouislider/nouislider.min.css">
    <link href="css/style.css" rel="stylesheet">
    <link href="css/theme.css" rel="stylesheet">
    <script>
      // Soft page-loader for user management open
      try {
        window.dispatchEvent(new Event('rewarity:data-loading'));
        window.addEventListener('load', function(){ setTimeout(function(){ window.dispatchEvent(new Event('rewarity:data-ready')); }, 350); });
      } catch(e) {}
    </script>
    <!-- Optional map CSS (Leaflet) for Dealer location picker -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <style>
      /* Uniform control sizes */
      .create-user-panel .form-control,
      .create-user-panel .form-select {
        height: 44px;
        padding-top: 10px;
        padding-bottom: 10px;
        border: 1px solid #dee2e6;
        box-shadow: none !important;
      }
      .create-user-panel .form-control:focus,
      .create-user-panel .form-select:focus {
        border-color: #cfd6dc;
        box-shadow: 0 0 0 2px rgba(0,0,0,0.03) !important;
      }
      .create-user-panel .readonly-field[readonly] {
        background-color: #f1f3f5 !important;
        color: #495057 !important;
        opacity: 1; /* keep text readable */
      }
      .create-user-panel .readonly-field::placeholder {
        color: #9aa0a6;
        opacity: 1;
      }
      .create-user-panel .form-select { background-color: #fff; }
      .create-user-panel label.form-label { color: #6b7280; font-weight: 500; }
      .create-user-panel .row.g-3 > [class^="col-"] { display: flex; flex-direction: column; }
      .create-user-panel .btn { height: 44px; padding: 0 18px; }
      .dealer-only { display: none; }
      .map-container { height: 360px; border-radius: 8px; overflow: hidden; }
      .map-search { display: flex; gap: 8px; margin-bottom: 10px; }
      .users-filter-card {
        background: #ffffff;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
        margin-bottom: 24px;
      }
      .users-filter-card .form-control,
      .users-filter-card .form-select {
        border-radius: 8px;
      }
      .users-table-card {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
      }
      .users-table-card .table thead th {
        background: #111827;
        color: #ffffff;
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 0.06em;
      }
      .users-table-card .table tbody td {
        vertical-align: middle;
      }
      .btn-add-user {
        border-radius: 8px;
        font-weight: 600;
      }
      .form-errors {
        border: 1px solid rgba(220, 53, 69, 0.35);
        background: rgba(220, 53, 69, 0.1);
        color: #842029;
        border-radius: 8px;
        padding: 12px 16px;
        margin-bottom: 16px;
      }
      .form-errors ul {
        margin: 0;
        padding-left: 18px;
      }
      .create-user-panel {
        background: #f9fafb;
        border: 1px dashed #d1d5db;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 32px;
        display: none;
      }
      .create-user-panel.active {
        display: block;
      }
    </style>
</head>
<body>
  <div id="preloader">
    <div class="waviy">
       <span style="--i:1">R</span>
       <span style="--i:2">E</span>
       <span style="--i:3">W</span>
       <span style="--i:4">A</span>
       <span style="--i:5">R</span>
       <span style="--i:6">I</span>
       <span style="--i:7">T</span>
       <span style="--i:8">Y</span>
    </div>
  </div>
  <?php
  require __DIR__ . '/layout/header.php';
  require __DIR__ . '/layout/sidebar.php';
?>
<div class="content-body">
            <div class="container-fluid">
                <?php if ($successMessage): ?>
                  <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($successMessage); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>
                <?php endif; ?>

                <?php if ($errorMessage): ?>
                  <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($errorMessage); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="mb-0"><i class="las la-users me-2"></i>User Management</h4>
                    <button type="button" class="btn btn-success btn-add-user" id="toggleUserForm"><i class="las la-user-plus me-2"></i>Add New User</button>
                </div>

                <div id="createUserPanel" class="create-user-panel <?php echo !empty($formErrors) ? 'active' : ''; ?>">
                    <h5 class="mb-3">New User Details</h5>
                    <?php if (!empty($formErrors)): ?>
                      <div class="form-errors">
                        <strong>Please fix the following:</strong>
                        <ul>
                          <?php foreach ($formErrors as $message): ?>
                            <li><?php echo htmlspecialchars($message); ?></li>
                          <?php endforeach; ?>
                        </ul>
                      </div>
                    <?php endif; ?>
                    <form method="post" action="/admin/users.php" class="row g-3">
                        <input type="hidden" name="action" value="create_user">
                        <div class="col-md-6">
                            <label class="form-label">Full Name<span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($formData['name']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email<span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($formData['email']); ?>" required>
                        </div>
                        <div class="col-md-6 password-fields">
                            <label class="form-label">Password<span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="col-md-6 password-fields">
                            <label class="form-label">Confirm Password<span class="text-danger">*</span></label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">User Type<span class="text-danger">*</span></label>
                            <select name="user_type_id" id="userTypeSelect" class="form-select" required data-dealer-id="<?php echo $dealerTypeId !== null ? (int)$dealerTypeId : ''; ?>">
                                <option value="">Select user type</option>
                                <?php foreach ($userTypes as $type): ?>
                                  <?php $label = display_user_type_label($type['typename']); ?>
                                  <option value="<?php echo (int)$type['id']; ?>" <?php echo $formData['user_type_id'] === (string)$type['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Mobile</label>
                            <input type="text" name="mobile" class="form-control" value="<?php echo htmlspecialchars($formData['mobile']); ?>" placeholder="e.g. 9876543210">
                        </div>
                        <div class="col-md-4 address-fields">
                            <label class="form-label">Pincode</label>
                            <input type="text" name="pincode" id="pincodeInput" class="form-control" value="<?php echo htmlspecialchars($formData['pincode']); ?>" placeholder="e.g. 110044" maxlength="10">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <div class="form-check form-switch pt-2">
                              <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="isActiveSwitch" <?php echo $formData['is_active'] === '1' ? 'checked' : ''; ?>>
                              <label class="form-check-label" for="isActiveSwitch">Active</label>
                            </div>
                        </div>
                        <div class="col-md-6 address-fields">
                            <label class="form-label">Address Line 1</label>
                            <input type="text" name="address1" class="form-control" value="<?php echo htmlspecialchars($formData['address1']); ?>">
                        </div>
                        <div class="col-md-6 address-fields">
                            <label class="form-label">Address Line 2</label>
                            <input type="text" name="address2" class="form-control" value="<?php echo htmlspecialchars($formData['address2']); ?>">
                        </div>
                        <div class="col-md-6 address-fields">
                            <label class="form-label">City</label>
                            <input type="text" id="cityName" class="form-control readonly-field" value="" placeholder="Auto from pincode" readonly>
                            <input type="hidden" name="city_id" id="cityId" value="<?php echo isset($cityId) && $cityId !== null ? (int)$cityId : ''; ?>">
                        </div>
                        <div class="col-md-6 address-fields">
                            <label class="form-label">State</label>
                            <input type="text" id="stateName" class="form-control readonly-field" value="" placeholder="Auto from pincode" readonly>
                            <input type="hidden" name="state_id" id="stateId" value="<?php echo isset($stateId) && $stateId !== null ? (int)$stateId : ''; ?>">
                        </div>
                        <div class="col-md-6 address-fields">
                            <label class="form-label">Country</label>
                            <input type="text" id="countryName" class="form-control readonly-field" value="" placeholder="Auto from pincode" readonly>
                            <input type="hidden" name="country_id" id="countryId" value="<?php echo isset($countryId) && $countryId !== null ? (int)$countryId : ''; ?>">
                        </div>

                        <!-- Dealer-only: Location picker via map -->
                        <div class="col-md-6 dealer-only">
                            <label class="form-label">Latitude</label>
                            <input type="text" id="latDisplay" class="form-control readonly-field" value="" placeholder="Pick from map" readonly>
                            <input type="hidden" name="latitude" id="latitude" value="">
                        </div>
                        <div class="col-md-6 dealer-only">
                            <label class="form-label">Longitude</label>
                            <input type="text" id="lngDisplay" class="form-control readonly-field" value="" placeholder="Pick from map" readonly>
                            <input type="hidden" name="longitude" id="longitude" value="">
                        </div>
                        <div class="col-12 dealer-only d-flex gap-2">
                            <button type="button" class="btn btn-outline-primary" id="openMapBtn">Pick Location on Map</button>
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary">Save User</button>
                        </div>
                    </form>
                </div>

                <div class="users-filter-card">
                    <form class="row g-3" method="get" action="/admin/users.php">
                        <div class="col-md-3">
                            <label class="form-label">Search by Name</label>
                            <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($filters['name']); ?>" placeholder="e.g. John">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Search by Mobile</label>
                            <input type="text" class="form-control" name="mobile" value="<?php echo htmlspecialchars($filters['mobile']); ?>" placeholder="e.g. 9876">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">User Type</label>
                            <select name="user_type" class="form-select">
                                <option value="">All User Types</option>
                                <?php foreach ($userTypes as $type): ?>
                                  <?php $label = display_user_type_label($type['typename']); ?>
                                  <option value="<?php echo (int)$type['id']; ?>" <?php echo $filters['user_type'] === (string)$type['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="active" <?php echo $filters['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $filters['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="col-12 d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><i class="las la-search me-1"></i>Search</button>
                            <a href="/admin/users.php" class="btn btn-light border"><i class="las la-sync me-1"></i>Reset</a>
                        </div>
                    </form>
                </div>

                <div class="users-table-card">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">User ID</th>
                                    <th scope="col">Name</th>
                                    <th scope="col">Email</th>
                                    <th scope="col">Mobile</th>
                                    <th scope="col">User Type</th>
                                    <th scope="col">Address</th>
                                    <th scope="col">Created Date</th>
                                    <th scope="col">Status</th>
                                    <th scope="col" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                  <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">No users found for the selected filters.</td>
                                  </tr>
                                <?php else: ?>
                                  <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo (int)$user['Id']; ?></td>
                                        <td><?php echo htmlspecialchars($user['UserName'] ?? '—'); ?></td>
                                        <td><?php echo htmlspecialchars($user['Email'] ?? '—'); ?></td>
                                        <td><?php echo htmlspecialchars($user['MobileNumber'] ?? '—'); ?></td>
                                        <td><?php echo htmlspecialchars(display_user_type_label($user['UserType'] ?? '')); ?></td>
                                        <td><?php echo render_address($user); ?></td>
                                        <td><?php echo htmlspecialchars($user['CreatedOn'] ? date('Y-m-d', strtotime($user['CreatedOn'])) : '—'); ?></td>
                                        <td><?php echo render_status_badge($user['IsActive']); ?></td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-primary js-view-user" data-user-id="<?php echo (int)$user['Id']; ?>" title="View"><i class="las la-eye"></i></button>
                                                <button type="button" class="btn btn-sm btn-outline-warning js-edit-user" data-user-id="<?php echo (int)$user['Id']; ?>" title="Edit"><i class="las la-edit"></i></button>
                                                <button type="button" class="btn btn-sm btn-outline-danger js-disable-user" data-user-id="<?php echo (int)$user['Id']; ?>" title="Disable"><i class="las la-ban"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                  <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>


            </div>
        </div>

        <!-- View/Edit Modal -->
        <div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="userModalTitle">User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <form id="userEditForm" class="row g-3">
                  <input type="hidden" id="editUserId">
                  <div class="col-md-6">
                    <label class="form-label">Full Name</label>
                    <input type="text" id="editName" class="form-control" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" id="editEmail" class="form-control" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Mobile</label>
                    <input type="text" id="editMobile" class="form-control">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">User Type</label>
                    <select id="editUserType" class="form-select">
                      <?php foreach ($userTypes as $type): $label = display_user_type_label($type['typename']); ?>
                        <option value="<?php echo (int)$type['id']; ?>"><?php echo htmlspecialchars($label); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select id="editStatus" class="form-select">
                      <option value="1">Active</option>
                      <option value="0">Inactive</option>
                    </select>
                  </div>
                </form>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveUserBtn">Save changes</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Map modal for Dealer location -->
        <div class="modal fade" id="mapPickerModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Pick Dealer Location</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <div class="map-search">
                  <input type="text" id="mapPincodeSearch" class="form-control" placeholder="Enter pincode to center map">
                  <button type="button" class="btn btn-light border" id="mapSearchBtn">Go</button>
                </div>
                <div id="dealerMap" class="map-container"></div>
                <div class="mt-2 small text-muted">Click on map to set location. Drag the marker to adjust.</div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="useLocationBtn" data-bs-dismiss="modal">Use this location</button>
              </div>
            </div>
          </div>
        </div>

        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        <script>
          document.addEventListener('DOMContentLoaded', function () {
            const toggleButton = document.getElementById('toggleUserForm');
            const panel = document.getElementById('createUserPanel');
            if (toggleButton && panel) {
              toggleButton.addEventListener('click', function () {
                panel.classList.toggle('active');
              });
            }

            // Toggle address vs dealer-only map fields based on user type
            const userTypeSelect = document.getElementById('userTypeSelect');
            const dealerId = parseInt(userTypeSelect?.dataset?.dealerId || '');
            function toggleDealerFields(){
              const isDealer = userTypeSelect && dealerId && (parseInt(userTypeSelect.value || '0') === dealerId);
              document.querySelectorAll('.address-fields').forEach(el => el.style.display = isDealer ? 'none' : 'block');
              document.querySelectorAll('.dealer-only').forEach(el => el.style.display = isDealer ? 'block' : 'none');
              // Toggle password fields for Dealer (hide + remove required)
              document.querySelectorAll('.password-fields').forEach(el => el.style.display = isDealer ? 'none' : 'block');
              document.querySelectorAll('.password-fields input').forEach(inp => {
                if (isDealer) {
                  inp.removeAttribute('required');
                  inp.value = '';
                } else {
                  inp.setAttribute('required','required');
                }
              });
              if (!isDealer) {
                // Clear coordinates if not dealer
                const lat = document.getElementById('latitude');
                const lng = document.getElementById('longitude');
                const latD = document.getElementById('latDisplay');
                const lngD = document.getElementById('lngDisplay');
                if (lat) lat.value = '';
                if (lng) lng.value = '';
                if (latD) latD.value = '';
                if (lngD) lngD.value = '';
              }
            }
            if (userTypeSelect) {
              userTypeSelect.addEventListener('change', toggleDealerFields);
              toggleDealerFields(); // initial
            }

            // Pincode auto-resolve
            const pin = document.getElementById('pincodeInput');
            const cityName = document.getElementById('cityName');
            const stateName = document.getElementById('stateName');
            const countryName = document.getElementById('countryName');
            const cityId = document.getElementById('cityId');
            const stateId = document.getElementById('stateId');
            const countryId = document.getElementById('countryId');
            async function resolvePincode(value){
              const v = (value || '').trim();
              if (!v) return;
              try {
                const res = await fetch(`/api/pincode.php?pincode=${encodeURIComponent(v)}&autolink=true`);
                if (!res.ok) throw new Error('lookup failed');
                const data = await res.json();
                cityName.value = (data && data.city && data.city.name) ? data.city.name : '';
                stateName.value = (data && data.state && data.state.name) ? data.state.name : '';
                countryName.value = (data && data.country && data.country.name) ? data.country.name : '';
                cityId.value = (data && data.city && data.city.id) ? data.city.id : '';
                stateId.value = (data && data.state && data.state.id) ? data.state.id : '';
                countryId.value = (data && data.country && data.country.id) ? data.country.id : '';
              } catch (e) {
                cityName.value = stateName.value = countryName.value = '';
                cityId.value = stateId.value = countryId.value = '';
              }
            }
            if (pin){
              pin.addEventListener('change', (e)=> resolvePincode(e.target.value));
              pin.addEventListener('blur', (e)=> resolvePincode(e.target.value));
              // Prevent Enter from submitting the form when focusing pincode
              pin.addEventListener('keydown', function(e){
                if (e.key === 'Enter') {
                  e.preventDefault();
                  resolvePincode(pin.value);
                }
              });
            }

            // Dealer map picker (Leaflet)
            let map, marker;
            const mapModalEl = document.getElementById('mapPickerModal');
            const openMapBtn = document.getElementById('openMapBtn');
            const useLocationBtn = document.getElementById('useLocationBtn');
            const mapSearchBtn = document.getElementById('mapSearchBtn');
            const mapSearchInput = document.getElementById('mapPincodeSearch');
            const latField = document.getElementById('latitude');
            const lngField = document.getElementById('longitude');
            const latDisp = document.getElementById('latDisplay');
            const lngDisp = document.getElementById('lngDisplay');
            function initMap(){
              if (map) { setTimeout(()=> map.invalidateSize(), 150); return; }
              map = L.map('dealerMap').setView([20.5937, 78.9629], 5); // India center
              L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
              }).addTo(map);
              function setMarker(lat, lng){
                if (!isFinite(lat) || !isFinite(lng)) return;
                if (!marker) {
                  marker = L.marker([lat, lng], {draggable:true}).addTo(map);
                  marker.on('dragend', ()=>{
                    const p = marker.getLatLng();
                    latDisp.value = latField.value = p.lat.toFixed(6);
                    lngDisp.value = lngField.value = p.lng.toFixed(6);
                  });
                } else {
                  marker.setLatLng([lat, lng]);
                }
                map.setView([lat, lng], 15);
                latDisp.value = latField.value = lat.toFixed(6);
                lngDisp.value = lngField.value = lng.toFixed(6);
              }
              map.on('click', (e)=> setMarker(e.latlng.lat, e.latlng.lng));
              // If we already have values, show them
              const exLat = parseFloat(latField.value || '');
              const exLng = parseFloat(lngField.value || '');
              if (isFinite(exLat) && isFinite(exLng)) setMarker(exLat, exLng);
            }
            // Bootstrap modal events
            if (openMapBtn && mapModalEl) {
              openMapBtn.addEventListener('click', ()=>{
                const modal = (typeof bootstrap !== 'undefined') ? bootstrap.Modal.getOrCreateInstance(mapModalEl) : null;
                modal?.show();
              });
            }
            mapModalEl?.addEventListener('shown.bs.modal', initMap);
            useLocationBtn?.addEventListener('click', ()=>{ /* values already set via marker */ });
            mapSearchBtn?.addEventListener('click', async ()=>{
              const q = (mapSearchInput?.value || '').trim();
              if (!q) return;
              try {
                // Use Nominatim to geocode pincode within India
                const url = `https://nominatim.openstreetmap.org/search?format=json&limit=1&country=India&postalcode=${encodeURIComponent(q)}`;
                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                const arr = await res.json();
                if (Array.isArray(arr) && arr[0]) {
                  const lat = parseFloat(arr[0].lat), lon = parseFloat(arr[0].lon);
                  if (isFinite(lat) && isFinite(lon)) {
                    initMap();
                    map.setView([lat, lon], 13);
                  }
                }
              } catch (err) {
                // ignore
              }
            });

            // Users actions
            const userModalEl = document.getElementById('userModal');
            const userModal = (typeof bootstrap !== 'undefined') ? bootstrap.Modal.getOrCreateInstance(userModalEl) : null;
            const titleEl = document.getElementById('userModalTitle');
            const editForm = document.getElementById('userEditForm');
            const saveBtn = document.getElementById('saveUserBtn');

            async function fetchUser(id){
              const res = await fetch(`/admin/user_actions.php?action=show&id=${id}`);
              if (!res.ok) throw new Error('Unable to load user');
              return res.json();
            }
            async function updateUser(payload){
              const res = await fetch('/admin/user_actions.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)});
              if (!res.ok) throw new Error('Update failed');
              return res.json();
            }
            async function disableUser(id){
              const res = await fetch('/admin/user_actions.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'disable', id})});
              if (!res.ok) throw new Error('Disable failed');
            }

            function fillForm(d, readOnly=false){
              document.getElementById('editUserId').value = d.id;
              document.getElementById('editName').value = d.name || '';
              document.getElementById('editEmail').value = d.email || '';
              document.getElementById('editMobile').value = d.mobile || '';
              document.getElementById('editUserType').value = d.user_type_id || '';
              document.getElementById('editStatus').value = d.is_active ? '1' : '0';
              editForm.querySelectorAll('input,select').forEach(el=>{ el.disabled = readOnly; });
              saveBtn.style.display = readOnly ? 'none' : 'inline-block';
            }

            document.querySelectorAll('.js-view-user').forEach(btn => btn.addEventListener('click', async (e)=>{
              const id = e.currentTarget.dataset.userId;
              try{ const d = await fetchUser(id); fillForm(d, true); titleEl.textContent = 'View User'; userModal?.show(); }catch(err){ alert(err.message); }
            }));

            document.querySelectorAll('.js-edit-user').forEach(btn => btn.addEventListener('click', async (e)=>{
              const id = e.currentTarget.dataset.userId;
              try{ const d = await fetchUser(id); fillForm(d, false); titleEl.textContent = 'Edit User'; userModal?.show(); }catch(err){ alert(err.message); }
            }));

            saveBtn?.addEventListener('click', async ()=>{
              const payload = {
                action: 'update',
                id: document.getElementById('editUserId').value,
                name: document.getElementById('editName').value,
                email: document.getElementById('editEmail').value,
                mobile: document.getElementById('editMobile').value,
                user_type_id: document.getElementById('editUserType').value,
                is_active: document.getElementById('editStatus').value
              };
              try{ await updateUser(payload); location.reload(); }catch(err){ alert(err.message); }
            });

            document.querySelectorAll('.js-disable-user').forEach(btn => btn.addEventListener('click', async (e)=>{
              const id = e.currentTarget.dataset.userId;
              if (!confirm('Disable this user?')) return;
              try{ await disableUser(id); location.reload(); }catch(err){ alert(err.message); }
            }));
          });
        </script>

        <?php require __DIR__ . '/layout/footer.php'; ?>
</body>
</html>
