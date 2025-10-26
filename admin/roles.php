<?php
require __DIR__ . '/layout/init.php';

$currentPage = 'roles';
$headerTitle = 'User Roles';
$assetBase = '/Dashborad';

// Authorization: only Super Admin/Admin can access
if (empty($canManageRoles)) {
  header('Location: /admin/dashboard.php');
  exit;
}

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
$editing = null;

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

// Update role handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_user_type') {
  $id = (int)($_POST['id'] ?? 0);
  $newTypeName = trim((string)($_POST['typename'] ?? ''));
  $newTypeDesc = trim((string)($_POST['description'] ?? ''));
  if ($id <= 0 || $newTypeName === '') {
    $errorMessage = 'Invalid role update request.';
  } else {
    $code = strtoupper(preg_replace('/[^A-Z0-9_]+/i', '_', $newTypeName));
    try {
      $dup = $conn->prepare('SELECT id FROM user_type WHERE UPPER(typename) = ? AND id <> ? LIMIT 1');
      $dup->bind_param('si', $code, $id);
      $dup->execute();
      if ($dup->get_result()->num_rows > 0) {
        $formError = 'Another role with same name exists.';
      }
      $dup->close();

      if (!$formError) {
        $desc = $newTypeDesc !== '' ? $newTypeDesc : null;
        $upd = $conn->prepare('UPDATE user_type SET typename = ?, description = ? WHERE id = ?');
        $upd->bind_param('ssi', $code, $desc, $id);
        $upd->execute();
        $upd->close();
        $successMessage = 'User role updated successfully.';
      }
    } catch (mysqli_sql_exception $e) {
      $errorMessage = 'Failed to update role: ' . $e->getMessage();
    }
  }
}

// Delete role handler (only if not in use)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_user_type') {
  $id = (int)($_POST['id'] ?? 0);
  if ($id <= 0) {
    $errorMessage = 'Invalid delete request.';
  } else {
    try {
      $inUse = 0;
      if ($res = $conn->prepare('SELECT COUNT(*) AS c FROM user_master WHERE UserTypeId = ?')) {
        $res->bind_param('i', $id);
        $res->execute();
        $r = $res->get_result()->fetch_assoc();
        $inUse = (int)($r['c'] ?? 0);
        $res->close();
      }
      if ($inUse > 0) {
        $errorMessage = 'Cannot delete: role is assigned to users.';
      } else {
        $del = $conn->prepare('DELETE FROM user_type WHERE id = ?');
        $del->bind_param('i', $id);
        $del->execute();
        $del->close();
        $successMessage = 'User role deleted.';
      }
    } catch (mysqli_sql_exception $e) {
      $errorMessage = 'Failed to delete role: ' . $e->getMessage();
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
    <script>try{var t=localStorage.getItem('rewarity_theme')||'light';var a=localStorage.getItem('rewarity_accent')||'green';var h=document.documentElement;h.setAttribute('data-theme',t);h.setAttribute('data-accent',a);}catch(e){}</script>
    <link href="css/style.css" rel="stylesheet">
    <link href="css/theme.css" rel="stylesheet">
    <script>
      // Soft page-loader: show brief branded loader on open for consistency
      try {
        window.dispatchEvent(new Event('rewarity:data-loading'));
        window.addEventListener('load', function(){ setTimeout(function(){ window.dispatchEvent(new Event('rewarity:data-ready')); }, 350); });
      } catch(e) {}
    </script>
    <style>
      .role-badge { background:#fde2e2; color:#d12a2a; border-radius:8px; padding:2px 8px; font-size:12px; font-weight:600; }
      .card-soft { border-radius:14px; box-shadow:0 8px 24px rgba(0,0,0,0.06); border:0; }
      .btn-success { background:#1DB954; border-color:#1DB954; }
      .btn-success:hover { background:#17a84e; border-color:#17a84e; }
      .table > :not(caption) > * > * { vertical-align: middle; }
      /* Make create-role form stay on a single line on wide screens */
      @media (min-width: 992px) {
        .role-form { flex-wrap: nowrap !important; gap: 16px; }
        .role-form .col-md-4, .role-form .col-md-6 { flex: 1 1 auto; max-width: none; }
        .role-form .col-action { flex: 0 0 220px; max-width: 220px; }
        .role-form .form-text { display: none; }
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

    <div class="card card-soft">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Create New Role</h5>
      </div>
      <div class="card-body">
        <?php if ($formError): ?>
          <div class="alert alert-danger"><?php echo htmlspecialchars($formError); ?></div>
        <?php endif; ?>
        <form class="row g-3 align-items-end role-form" method="post" action="/admin/roles.php">
          <input type="hidden" name="action" value="create_user_type">
          <div class="col-md-4">
            <label class="form-label">Role Name<span class="text-danger">*</span></label>
            <input type="text" name="typename" class="form-control" placeholder="e.g. Manager or MANAGER" required>
            <small class="form-text text-muted mb-0">Stored as uppercase code; displayed prettily.</small>
          </div>
          <div class="col-md-6">
            <label class="form-label">Description</label>
            <input type="text" name="description" class="form-control" placeholder="Optional description">
          </div>
          <div class="col-md-2 d-flex align-items-end col-action">
            <button type="submit" class="btn btn-success w-100">Add Role</button>
          </div>
        </form>
      </div>
    </div>

    <div class="card card-soft mt-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Existing Roles</h5>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table align-middle">
            <thead>
              <tr>
                <th style="width:100px;">ID</th>
                <th>Role</th>
                <th>Code</th>
                <th>Description</th>
                <th style="width:160px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($roles)): ?>
                <tr><td colspan="4" class="text-center text-muted py-3">No roles found.</td></tr>
              <?php else: foreach ($roles as $r): $label = display_user_type_label($r['typename']); ?>
                <tr>
                  <td><?php echo (int)$r['id']; ?></td>
                  <td><?php echo htmlspecialchars($label); ?></td>
                  <td><span class="role-badge"><?php echo htmlspecialchars($r['typename']); ?></span></td>
                  <td><?php echo htmlspecialchars($r['description'] ?? ''); ?></td>
                  <td>
                    <div class="d-flex gap-2">
                      <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editRoleModal<?php echo (int)$r['id']; ?>">Edit</button>
                      <form method="post" action="/admin/roles.php" onsubmit="return confirm('Delete this role?');">
                        <input type="hidden" name="action" value="delete_user_type">
                        <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                      </form>
                    </div>
                    <!-- Edit Modal -->
                    <div class="modal fade" id="editRoleModal<?php echo (int)$r['id']; ?>" tabindex="-1" aria-hidden="true">
                      <div class="modal-dialog">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h5 class="modal-title">Edit Role</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <form method="post" action="/admin/roles.php">
                            <input type="hidden" name="action" value="update_user_type">
                            <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                            <div class="modal-body">
                              <div class="mb-3">
                                <label class="form-label">Role Name</label>
                                <input type="text" class="form-control" name="typename" value="<?php echo htmlspecialchars($r['typename']); ?>" required>
                                <small class="text-muted">Stored as uppercase code; displayed prettily.</small>
                              </div>
                              <div class="mb-3">
                                <label class="form-label">Description</label>
                                <input type="text" class="form-control" name="description" value="<?php echo htmlspecialchars($r['description'] ?? ''); ?>">
                              </div>
                            </div>
                            <div class="modal-footer">
                              <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                              <button type="submit" class="btn btn-success">Save Changes</button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>
                  </td>
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
