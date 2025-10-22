<?php
require __DIR__ . '/layout/init.php';

$currentPage = 'roles';
$headerTitle = 'User Roles';
$assetBase = '/Dashborad';

function next_numeric_id(mysqli $conn, string $table, string $column = 'Id'): int
{
  $sql = sprintf('SELECT COALESCE(MAX(`%s`), 0) + 1 AS next_id FROM `%s`', $column, $table);
  $result = $conn->query($sql);
  if (!$result) throw new mysqli_sql_exception('Failed to determine next identifier.');
  $row = $result->fetch_assoc();
  $result->free();
  return (int)($row['next_id'] ?? 1);
}

function display_user_type_label(?string $code): string
{
  if ($code === null || $code === '') return '—';
  return ucwords(strtolower(str_replace('_', ' ', $code)));
}

$successMessage = null;
$errorMessage = null;
$formError = null;

// Create role handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_user_type') {
  $newTypeName = trim((string)($_POST['typename'] ?? ''));
  $newTypeDesc = trim((string)($_POST['description'] ?? ''));
  if ($newTypeName === '') {
    $formError = 'Role name is required.';
  } else {
    $code = strtoupper(preg_replace('/[^A-Z0-9_]+/i', '_', $newTypeName));
    try {
      $dup = $conn->prepare('SELECT id FROM user_type WHERE UPPER(typename) = ? LIMIT 1');
      $dup->bind_param('s', $code);
      $dup->execute();
      if ($dup->get_result()->num_rows > 0) {
        $formError = 'Role already exists.';
      }
      $dup->close();

      if (!$formError) {
        $newId = next_numeric_id($conn, 'user_type', 'id');
        $ins = $conn->prepare('INSERT INTO user_type (id, typename, description) VALUES (?, ?, ?)');
        $desc = $newTypeDesc !== '' ? $newTypeDesc : null;
        $ins->bind_param('iss', $newId, $code, $desc);
        $ins->execute();
        $ins->close();
        $successMessage = 'User role created successfully.';
      }
    } catch (mysqli_sql_exception $e) {
      $errorMessage = 'Failed to create role: ' . $e->getMessage();
    }
  }
}

// Load roles
$roles = [];
try {
  if ($typeResult = $conn->query('SELECT id, typename, description FROM user_type ORDER BY typename')) {
    while ($row = $typeResult->fetch_assoc()) $roles[] = $row;
    $typeResult->free();
  }
} catch (mysqli_sql_exception $e) {
  $errorMessage = 'Unable to load user roles: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>User Roles - Rewarity</title>
    <base href="<?php echo htmlspecialchars($assetBase, ENT_QUOTES); ?>/">
    <link href="vendor/jquery-nice-select/css/nice-select.css" rel="stylesheet">
    <link rel="stylesheet" href="vendor/nouislider/nouislider.min.css">
    <link href="css/style.css" rel="stylesheet">
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

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Create New Role</h5>
      </div>
      <div class="card-body">
        <?php if ($formError): ?>
          <div class="alert alert-danger"><?php echo htmlspecialchars($formError); ?></div>
        <?php endif; ?>
        <form class="row g-3" method="post" action="/admin/roles.php">
          <input type="hidden" name="action" value="create_user_type">
          <div class="col-md-4">
            <label class="form-label">Role Name<span class="text-danger">*</span></label>
            <input type="text" name="typename" class="form-control" placeholder="e.g. Manager or MANAGER" required>
            <small class="text-muted">Stored as uppercase code; displayed prettily.</small>
          </div>
          <div class="col-md-6">
            <label class="form-label">Description</label>
            <input type="text" name="description" class="form-control" placeholder="Optional description">
          </div>
          <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">Add Role</button>
          </div>
        </form>
      </div>
    </div>

    <div class="card mt-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Existing Roles</h5>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-striped align-middle">
            <thead>
              <tr>
                <th style="width:100px;">ID</th>
                <th>Role</th>
                <th>Code</th>
                <th>Description</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($roles)): ?>
                <tr><td colspan="4" class="text-center text-muted py-3">No roles found.</td></tr>
              <?php else: foreach ($roles as $r): $label = display_user_type_label($r['typename']); ?>
                <tr>
                  <td><?php echo (int)$r['id']; ?></td>
                  <td><?php echo htmlspecialchars($label); ?></td>
                  <td><code><?php echo htmlspecialchars($r['typename']); ?></code></td>
                  <td><?php echo htmlspecialchars($r['description'] ?? ''); ?></td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/layout/footer.php'; ?>
</body>
</html>
