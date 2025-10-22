<?php
require __DIR__ . '/layout/init.php';

header('Content-Type: application/json; charset=utf-8');

function json_out($code, $data){ http_response_code($code); echo json_encode($data); exit; }

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? null;
if ($method === 'POST') {
  $raw = file_get_contents('php://input');
  $in = json_decode($raw, true) ?: [];
  $action = $in['action'] ?? $action;
}

try {
  if ($action === 'show') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) json_out(422, ['error'=>'Invalid id']);
    $sql = "SELECT u.Id, u.UserName, u.Email, u.IsActive, u.UserTypeId, m.MobileNumber,
                   ut.typename AS UserType
            FROM user_master u
            LEFT JOIN mobile_master m ON m.UserId = u.Id AND m.IsPrimary = 1
            LEFT JOIN user_type ut ON ut.id = u.UserTypeId
            WHERE u.Id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) json_out(404, ['error'=>'Not found']);
    json_out(200, [
      'id' => (int)$row['Id'],
      'name' => $row['UserName'],
      'email' => $row['Email'],
      'mobile' => $row['MobileNumber'] ?? '',
      'user_type_id' => $row['UserTypeId'] !== null ? (int)$row['UserTypeId'] : null,
      'user_type_name' => $row['UserType'],
      'is_active' => ((int)$row['IsActive'] === 1)
    ]);
  }

  if ($method === 'POST' && $action === 'update') {
    $id = (int)($in['id'] ?? 0);
    if ($id <= 0) json_out(422, ['error'=>'Invalid id']);
    $name = trim((string)($in['name'] ?? ''));
    $email = trim((string)($in['email'] ?? ''));
    $mobile = trim((string)($in['mobile'] ?? ''));
    $userTypeId = isset($in['user_type_id']) ? (int)$in['user_type_id'] : null;
    $isActive = isset($in['is_active']) ? (int)$in['is_active'] : 1;

    $stmt = $conn->prepare('UPDATE user_master SET UserName = ?, Email = ?, UserTypeId = ?, IsActive = ? WHERE Id = ?');
    $stmt->bind_param('ssiii', $name, $email, $userTypeId, $isActive, $id);
    $stmt->execute();
    $stmt->close();

    if ($mobile !== '') {
      // Upsert primary mobile
      $check = $conn->prepare('SELECT Id FROM mobile_master WHERE UserId = ? AND IsPrimary = 1 LIMIT 1');
      $check->bind_param('i', $id);
      $check->execute();
      $r = $check->get_result()->fetch_assoc();
      $check->close();
      if ($r) {
        $mid = (int)$r['Id'];
        $u = $conn->prepare('UPDATE mobile_master SET MobileNumber = ? WHERE Id = ?');
        $u->bind_param('si', $mobile, $mid);
        $u->execute();
        $u->close();
      } else {
        $mid = next_numeric_id($conn, 'mobile_master');
        $isPrimary = 1; $mActive = 1;
        $ins = $conn->prepare('INSERT INTO mobile_master (Id, MobileNumber, IsPrimary, IsActive, UserId) VALUES (?, ?, ?, ?, ?)');
        $ins->bind_param('isiii', $mid, $mobile, $isPrimary, $mActive, $id);
        $ins->execute();
        $ins->close();
      }
    }
    json_out(200, ['message'=>'Updated']);
  }

  if ($method === 'POST' && $action === 'disable') {
    $id = (int)($in['id'] ?? 0);
    if ($id <= 0) json_out(422, ['error'=>'Invalid id']);
    $stmt = $conn->prepare('UPDATE user_master SET IsActive = 0 WHERE Id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    json_out(200, ['message'=>'Disabled']);
  }

  json_out(405, ['error'=>'Method/action not allowed']);
} catch (mysqli_sql_exception $e) {
  json_out(500, ['error'=>'Server error','details'=>$e->getMessage()]);
}
$conn->set_charset('utf8mb4');

if (!function_exists('next_numeric_id')) {
  function next_numeric_id(mysqli $conn, string $table, string $column = 'Id'): int
  {
    $sql = sprintf('SELECT COALESCE(MAX(`%s`), 0) + 1 AS next_id FROM `%s`', $column, $table);
    $result = $conn->query($sql);
    if (!$result) throw new mysqli_sql_exception('Failed to determine next identifier.');
    $row = $result->fetch_assoc();
    $result->free();
    return (int)($row['next_id'] ?? 1);
  }
}
