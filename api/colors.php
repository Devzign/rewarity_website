<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');

// Admin/session protected
require_auth();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed']);
  exit;
}

try {
  $rows = [];
  $res = $conn->query('SELECT Id, ColorName, HexCode, IsActive FROM color_master WHERE IsActive = 1 ORDER BY ColorName');
  while ($row = $res->fetch_assoc()) {
    $rows[] = [
      'id' => (int)$row['Id'],
      'name' => $row['ColorName'],
      'hex' => $row['HexCode'] ?? null,
      'is_active' => (int)$row['IsActive'] === 1,
    ];
  }
  echo json_encode(['colors' => $rows]);
} catch (mysqli_sql_exception $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Failed to load colors', 'details' => $e->getMessage()]);
}

