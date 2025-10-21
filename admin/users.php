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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_user') {
  $formData['name'] = trim((string)($_POST['name'] ?? ''));
  $formData['email'] = trim((string)($_POST['email'] ?? ''));
  $formData['password'] = (string)($_POST['password'] ?? '');
  $formData['confirm_password'] = (string)($_POST['confirm_password'] ?? '');
  $formData['user_type_id'] = trim((string)($_POST['user_type_id'] ?? ''));
  $formData['mobile'] = trim((string)($_POST['mobile'] ?? ''));
  $formData['is_active'] = isset($_POST['is_active']) ? '1' : '0';
  $formData['address1'] = trim((string)($_POST['address1'] ?? ''));
  $formData['address2'] = trim((string)($_POST['address2'] ?? ''));
  $cityId = null;
  $stateId = null;
  $countryId = null;

  if ($formData['name'] === '') {
    $formErrors['name'] = 'Name is required.';
  }

  if ($formData['email'] === '' || !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
    $formErrors['email'] = 'A valid email is required.';
  }

  if ($formData['password'] === '' || strlen($formData['password']) < 6) {
    $formErrors['password'] = 'Password must be at least 6 characters long.';
  }

  if ($formData['password'] !== $formData['confirm_password']) {
    $formErrors['confirm_password'] = 'Passwords do not match.';
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

      if ($formData['address1'] !== '' || $formData['address2'] !== '' || $cityId || $stateId || $countryId) {
        $addressId = next_numeric_id($conn, 'address_master');
        $addressStmt = $conn->prepare(
          'INSERT INTO address_master (Id, Address1, Address2, CityId, StateId, CountryId) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $addressLine1Param = $formData['address1'] !== '' ? $formData['address1'] : null;
        $addressLine2Param = $formData['address2'] !== '' ? $formData['address2'] : null;
        $cityIdParam = $cityId ?? null;
        $stateIdParam = $stateId ?? null;
        $countryIdParam = $countryId ?? null;
        $addressStmt->bind_param(
          'issiii',
          $addressId,
          $addressLine1Param,
          $addressLine2Param,
          $cityIdParam,
          $stateIdParam,
          $countryIdParam
        );
        $addressStmt->execute();
        $addressStmt->close();
      }

      $passwordHash = password_hash($formData['password'], PASSWORD_DEFAULT);
      $isActiveValue = $formData['is_active'] === '1' ? 1 : 0;
      $userTypeIdValue = (int)$formData['user_type_id'];
      $nameParam = $formData['name'];
      $emailParam = $formData['email'];
      $addressParam = $addressId ?: null;
      $employeeParam = null;

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
      header('Location: users.php?created=1');
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
    <link href="vendor/jquery-nice-select/css/nice-select.css" rel="stylesheet">
    <link rel="stylesheet" href="vendor/nouislider/nouislider.min.css">
    <link href="css/style.css" rel="stylesheet">
    <style>
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
                    <form method="post" action="users.php" class="row g-3">
                        <input type="hidden" name="action" value="create_user">
                        <div class="col-md-6">
                            <label class="form-label">Full Name<span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($formData['name']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email<span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($formData['email']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password<span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirm Password<span class="text-danger">*</span></label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">User Type<span class="text-danger">*</span></label>
                            <select name="user_type_id" class="form-select" required>
                                <option value="">Select user type</option>
                                <?php foreach ($userTypes as $type): ?>
                                  <option value="<?php echo (int)$type['id']; ?>" <?php echo $formData['user_type_id'] === (string)$type['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($type['typename']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Mobile</label>
                            <input type="text" name="mobile" class="form-control" value="<?php echo htmlspecialchars($formData['mobile']); ?>" placeholder="e.g. 9876543210">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <div class="form-check form-switch pt-2">
                              <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="isActiveSwitch" <?php echo $formData['is_active'] === '1' ? 'checked' : ''; ?>>
                              <label class="form-check-label" for="isActiveSwitch">Active</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Address Line 1</label>
                            <input type="text" name="address1" class="form-control" value="<?php echo htmlspecialchars($formData['address1']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Address Line 2</label>
                            <input type="text" name="address2" class="form-control" value="<?php echo htmlspecialchars($formData['address2']); ?>">
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary">Save User</button>
                        </div>
                    </form>
                </div>

                <div class="users-filter-card">
                    <form class="row g-3" method="get" action="users.php">
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
                                  <option value="<?php echo (int)$type['id']; ?>" <?php echo $filters['user_type'] === (string)$type['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($type['typename']); ?></option>
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
                            <a href="users.php" class="btn btn-light border"><i class="las la-sync me-1"></i>Reset</a>
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
                                        <td><?php echo htmlspecialchars($user['UserType'] ?? '—'); ?></td>
                                        <td><?php echo render_address($user); ?></td>
                                        <td><?php echo htmlspecialchars($user['CreatedOn'] ? date('Y-m-d', strtotime($user['CreatedOn'])) : '—'); ?></td>
                                        <td><?php echo render_status_badge($user['IsActive']); ?></td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-primary" title="View" disabled><i class="las la-eye"></i></button>
                                                <button type="button" class="btn btn-sm btn-outline-warning" title="Edit" disabled><i class="las la-edit"></i></button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" title="Disable" disabled><i class="las la-ban"></i></button>
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

        <script>
          document.addEventListener('DOMContentLoaded', function () {
            const toggleButton = document.getElementById('toggleUserForm');
            const panel = document.getElementById('createUserPanel');
            if (toggleButton && panel) {
              toggleButton.addEventListener('click', function () {
                panel.classList.toggle('active');
              });
            }
          });
        </script>

        <?php require __DIR__ . '/layout/footer.php'; ?>
</body>
</html>
