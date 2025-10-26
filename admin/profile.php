<?php
require __DIR__ . '/layout/init.php';

$currentPage = 'profile';
$headerTitle = 'My Profile';
$assetBase = '/Dashborad';

// Only admins (session-based) reach here. Use admin_users table.
$adminId = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 0;
if ($adminId <= 0) { header('Location: /admin/login.php'); exit; }

// Fetch current admin
$admin = ['Email' => '', 'DisplayName' => ''];
try {
  $stmt = $conn->prepare('SELECT Email, DisplayName FROM admin_users WHERE Id = ? LIMIT 1');
  $stmt->bind_param('i', $adminId);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($row = $res->fetch_assoc()) { $admin = $row; }
  $stmt->close();
} catch (mysqli_sql_exception $e) { /* show defaults */ }

// Profile image helpers
$uploadDir = dirname(__DIR__) . '/uploads/admin_profiles';
if (!is_dir($uploadDir)) {
  @mkdir($uploadDir, 0775, true);
}
// Ensure directory is writable (best-effort)
if (is_dir($uploadDir) && !is_writable($uploadDir)) {
  @chmod($uploadDir, 0775);
}

function current_profile_image_path(int $id): ?string {
  $base = dirname(__DIR__) . '/uploads/admin_profiles/';
  foreach (['jpg','jpeg','png','webp'] as $ext) {
    $p = $base . $id . '.' . $ext;
    if (is_file($p)) return $p;
  }
  return null;
}
function current_profile_image_url(int $id): ?string {
  foreach (['jpg','jpeg','png','webp'] as $ext) {
    $p = '/uploads/admin_profiles/' . $id . '.' . $ext;
    if (is_file(dirname(__DIR__) . $p)) return $p . '?v=' . time();
  }
  return null;
}

$success = null; $error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  if ($action === 'update_profile') {
    $displayName = trim((string)($_POST['display_name'] ?? ''));
    $curr = (string)($_POST['current_password'] ?? '');
    $new = (string)($_POST['new_password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    // Update display name
    if ($displayName !== '') {
      try {
        $s = $conn->prepare('UPDATE admin_users SET DisplayName = ? WHERE Id = ?');
        $s->bind_param('si', $displayName, $adminId); $s->execute(); $s->close();
        $_SESSION['admin'] = $displayName; $admin['DisplayName'] = $displayName; $adminName = $displayName;
        $success = 'Profile updated.';
      } catch (mysqli_sql_exception $e) { $error = 'Unable to update profile name.'; }
    }

    // Update password (optional) if all provided
    if ($curr !== '' || $new !== '' || $confirm !== '') {
      if (strlen($new) < 6) { $error = 'New password must be at least 6 characters.'; }
      elseif ($new !== $confirm) { $error = 'New password and confirmation do not match.'; }
      else {
        try {
          $ps = $conn->prepare('SELECT PasswordHash FROM admin_users WHERE Id = ?');
          $ps->bind_param('i', $adminId); $ps->execute(); $pwRes = $ps->get_result(); $row = $pwRes->fetch_assoc(); $ps->close();
          $hash = (string)($row['PasswordHash'] ?? '');
          if ($hash === '' || password_verify($curr, $hash)) {
            $newHash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
            $us = $conn->prepare('UPDATE admin_users SET PasswordHash = ? WHERE Id = ?');
            $us->bind_param('si', $newHash, $adminId); $us->execute(); $us->close();
            $success = 'Password changed successfully.';
          } else { $error = 'Current password is incorrect.'; }
        } catch (mysqli_sql_exception $e) { $error = 'Unable to update password.'; }
      }
    }

    // Handle image upload if provided
    if (isset($_FILES['profile_image']) && is_array($_FILES['profile_image']) && ($_FILES['profile_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
      $f = $_FILES['profile_image'];
      if (($f['error'] ?? 0) === UPLOAD_ERR_OK) {
        if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
          $error = 'Upload directory is not writable: uploads/admin_profiles. Please set permissions (775).';
        } else {
          // Validate actual image type
          $imgInfo = @getimagesize($f['tmp_name']);
          $type = $imgInfo[2] ?? 0; // IMAGETYPE_*
          $ext = null;
          if ($type === IMAGETYPE_JPEG) $ext = 'jpg';
          elseif ($type === IMAGETYPE_PNG) $ext = 'png';
          elseif (defined('IMAGETYPE_WEBP') && $type === IMAGETYPE_WEBP) $ext = 'webp';
          if ($ext === null) {
            $error = 'Unsupported image type. Use JPG, PNG, or WEBP.';
          } elseif (($f['size'] ?? 0) > 2 * 1024 * 1024) {
            $error = 'Image too large. Max 2 MB.';
          } else {
            // Remove any existing variants
            foreach (['jpg','jpeg','png','webp'] as $e) {
              $p = $uploadDir . '/' . $adminId . '.' . $e; if (is_file($p)) @unlink($p);
            }
            $dest = $uploadDir . '/' . $adminId . '.' . $ext;
            if (!@move_uploaded_file($f['tmp_name'], $dest)) {
              // Fallback to rename if move_uploaded_file fails due to temp dir issues
              if (!@rename($f['tmp_name'], $dest)) {
                $error = 'Failed to save image to uploads/admin_profiles. Check permissions.';
              } else { @chmod($dest, 0644); $success = ($success ? $success.' ' : '') . 'Profile image updated.'; }
            } else {
              @chmod($dest, 0644);
              $success = ($success ? $success.' ' : '') . 'Profile image updated.';
            }
            // Refresh header image immediately on this page
            $photoUrl = current_profile_image_url($adminId);
            if ($photoUrl) { $profileImageUrl = $photoUrl; }
          }
        }
      } else {
        $error = 'Image upload failed (code ' . (int)$f['error'] . ').';
      }
    }
  }
}

$photoUrl = current_profile_image_url($adminId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My Profile - Rewarity</title>
  <base href="<?php echo htmlspecialchars($assetBase, ENT_QUOTES); ?>/">
  <script>try{var t=localStorage.getItem('rewarity_theme')||'light';var a=localStorage.getItem('rewarity_accent')||'green';var h=document.documentElement;h.setAttribute('data-theme',t);h.setAttribute('data-accent',a);}catch(e){}</script>
  <link href="css/style.css" rel="stylesheet">
  <link href="css/theme.css" rel="stylesheet">
  <style>
    /* Profile page layout tweaks */
    .profile-left .card { height: auto !important; border: 1px solid var(--border); }
    .profile-left .card-body { padding: 24px 20px; }
    .profile-form .card { border: 1px solid var(--border); }
    .profile-form .card-header { border-bottom: 1px solid var(--border); }
  </style>
</head>
<body>
  <div id="preloader">
    <div class="waviy">
      <span style="--i:1">R</span><span style="--i:2">E</span><span style="--i:3">W</span><span style="--i:4">A</span>
      <span style="--i:5">R</span><span style="--i:6">I</span><span style="--i:7">T</span><span style="--i:8">Y</span>
    </div>
  </div>
  <?php require __DIR__ . '/layout/header.php'; ?>
  <?php require __DIR__ . '/layout/sidebar.php'; ?>

  <div class="content-body">
    <div class="container-fluid">
      <div class="row g-4">
        <div class="col-xl-4 profile-left">
          <div class="card card-soft">
            <div class="card-body text-center">
              <div class="mb-3">
                <?php if ($photoUrl): ?>
                  <img src="<?php echo htmlspecialchars($photoUrl); ?>" alt="Profile" class="rounded-circle" width="120" height="120" style="object-fit:cover;">
                <?php else: ?>
                  <img src="images/ion/man (1).png" alt="Profile" class="rounded-circle" width="120" height="120" style="object-fit:cover;">
                <?php endif; ?>
              </div>
              <h4 class="mb-1"><?php echo htmlspecialchars($admin['DisplayName'] ?: 'Admin'); ?></h4>
              <div class="text-muted"><?php echo htmlspecialchars($admin['Email']); ?></div>
            </div>
          </div>
        </div>
        <div class="col-xl-8 profile-form">
          <div class="card card-soft">
            <div class="card-header"><h5 class="mb-0">Edit Profile</h5></div>
            <div class="card-body">
              <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
              <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
              <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_profile">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label">Display Name</label>
                    <input type="text" class="form-control" name="display_name" value="<?php echo htmlspecialchars($admin['DisplayName'] ?? ''); ?>">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($admin['Email']); ?>" readonly>
                  </div>
                  <div class="col-md-12">
                    <label class="form-label">Profile Image (JPG/PNG/WEBP, max 2MB)</label>
                    <input type="file" class="form-control" name="profile_image" accept="image/jpeg,image/png,image/webp">
                  </div>
                  <div class="col-12"><hr></div>
                  <div class="col-md-4">
                    <label class="form-label">Current Password</label>
                    <input type="password" class="form-control" name="current_password" autocomplete="current-password">
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">New Password</label>
                    <input type="password" class="form-control" name="new_password" autocomplete="new-password">
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" name="confirm_password" autocomplete="new-password">
                  </div>
                  <div class="col-12 d-flex justify-content-end">
                    <button type="submit" class="btn btn-success">Save Changes</button>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php require __DIR__ . '/layout/footer.php'; ?>
</body>
</html>
