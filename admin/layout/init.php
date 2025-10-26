<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!isset($_SESSION['admin'])) {
  header('Location: /admin/login.php');
  exit;
}

require_once __DIR__ . '/../../includes/config.php';

$assetBase = $assetBase ?? '/Dashborad';
$adminId = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null;
$adminName = (string)($_SESSION['admin'] ?? 'Admin');
$adminEmail = '';
$canManageRoles = false;

if ($adminId) {
  $stmt = $conn->prepare('SELECT Email, DisplayName FROM admin_users WHERE Id = ? LIMIT 1');
  if ($stmt) {
    $stmt->bind_param('i', $adminId);
    $stmt->execute();
    if ($result = $stmt->get_result()) {
      if ($row = $result->fetch_assoc()) {
        $adminEmail = (string)($row['Email'] ?? '');
        $fetchedName = (string)($row['DisplayName'] ?? '');
        if ($fetchedName !== '') {
          $adminName = $fetchedName;
          $_SESSION['admin'] = $adminName;
        }
        $nameForCheck = strtolower($adminName);
        if (str_contains($nameForCheck, 'super admin') || str_contains($nameForCheck, 'admin')) {
          $canManageRoles = true;
        }
        if ($adminEmail && strtolower($adminEmail) === 'admin@rewarity.com') {
          $canManageRoles = true;
        }
      }
      $result->free();
    }
    $stmt->close();
  }
}
