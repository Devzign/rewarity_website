<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');

// Enforce auth (Bearer or admin session)
require_auth();

$typesParam = isset($_GET['types']) ? (string)$_GET['types'] : '';
$singleType = isset($_GET['type']) ? (string)$_GET['type'] : '';

$typeNames = [];
if ($typesParam !== '') {
  $typeNames = array_filter(array_map('trim', explode(',', $typesParam)));
} elseif ($singleType !== '') {
  $typeNames = [trim($singleType)];
}

if (empty($typeNames)) {
  json_response(422, ['error' => 'Provide type or types query param (e.g., type=DEALER or types=DEALER,DISTRIBUTOR)']);
}

// Normalize to uppercase codes
$typeNames = array_map(function($t){ return strtoupper(str_replace(' ', '_', $t)); }, $typeNames);

try {
  // Build IN clause dynamically and fetch users for requested types
  $placeholders = implode(',', array_fill(0, count($typeNames), '?'));
  $sql = "SELECT u.Id, u.UserName, u.Email, u.IsActive, ut.typename AS TypeName
          FROM user_master u
          INNER JOIN user_type ut ON ut.id = u.UserTypeId
          WHERE UPPER(ut.typename) IN ($placeholders)
          ORDER BY ut.typename, u.UserName";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param(str_repeat('s', count($typeNames)), ...$typeNames);
  $stmt->execute();
  $res = $stmt->get_result();
  $rows = [];
  while ($row = $res->fetch_assoc()) {
    $rows[] = [
      'id' => (int)$row['Id'],
      'name' => $row['UserName'],
      'email' => $row['Email'],
      'is_active' => ((int)$row['IsActive'] === 1),
      'type' => strtoupper($row['TypeName'] ?? '')
    ];
  }
  $stmt->close();

  // Group by type for convenience
  $grouped = [];
  foreach ($rows as $r) {
    $grouped[$r['type']][] = $r;
  }

  json_response(200, ['users' => $rows, 'by_type' => $grouped]);
} catch (mysqli_sql_exception $e) {
  json_response(500, ['error' => 'Failed to fetch users by type', 'details' => $e->getMessage()]);
}

