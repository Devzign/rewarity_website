<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');
require_auth();

try {
  $sql = "SELECT u.Id, u.UserName, u.Email, u.IsActive
          FROM user_master u
          INNER JOIN user_type ut ON ut.id = u.UserTypeId
          WHERE UPPER(ut.typename) = 'DEALER'
          ORDER BY u.UserName";
  $res = $conn->query($sql);
  $rows = [];
  while ($row = $res->fetch_assoc()) {
    $rows[] = [
      'id' => (int)$row['Id'],
      'name' => $row['UserName'],
      'email' => $row['Email'],
      'is_active' => ((int)$row['IsActive'] === 1)
    ];
  }
  echo json_encode(['dealers' => $rows]);
} catch (mysqli_sql_exception $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Failed to fetch dealers', 'details' => $e->getMessage()]);
}

