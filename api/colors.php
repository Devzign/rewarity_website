<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');

// Admin/session protected
require_auth();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Unified error handling for JSON API
register_api_error_handler();

function ensure_color_table(mysqli $conn): void {
  try {
    $sql = "CREATE TABLE IF NOT EXISTS `color_master` (
      `Id` INT NOT NULL,
      `ColorName` VARCHAR(60) NOT NULL,
      `HexCode` VARCHAR(7) NULL,
      `IsActive` TINYINT(1) NOT NULL DEFAULT 1,
      `CreatedOn` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`Id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->query($sql);
  } catch (mysqli_sql_exception $e) {
    // ignore; subsequent queries will surface errors if any
  }
}

if ($method === 'GET') {
  ensure_color_table($conn);
  try {
    $rows = [];
    $includeAll = isset($_GET['all']) && (int)$_GET['all'] === 1;
    $sql = $includeAll
      ? 'SELECT Id, ColorName, HexCode, IsActive, CreatedOn FROM color_master ORDER BY ColorName'
      : 'SELECT Id, ColorName, HexCode, IsActive, CreatedOn FROM color_master WHERE IsActive = 1 ORDER BY ColorName';
    $res = $conn->query($sql);
    while ($row = $res->fetch_assoc()) {
      $rows[] = [
        'id' => (int)$row['Id'],
        'name' => $row['ColorName'],
        'hex' => $row['HexCode'] ?? null,
        'is_active' => (int)$row['IsActive'] === 1,
        'created_on' => $row['CreatedOn'] ? date('Y-m-d H:i:s', strtotime($row['CreatedOn'])) : null,
      ];
    }
    json_response(200, ['colors' => $rows]);
  } catch (mysqli_sql_exception $e) {
    json_response(500, ['error' => 'Failed to load colors', 'details' => $e->getMessage()]);
  }
  exit;
}

if ($method === 'POST') {
  ensure_color_table($conn);
  $payload = [];
  $isMultipart = isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false;
  if ($isMultipart) {
    $payload['id'] = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : null;
    $payload['name'] = trim((string)($_POST['name'] ?? ''));
    $payload['hex'] = isset($_POST['hex']) ? trim((string)$_POST['hex']) : null;
    $payload['is_active'] = isset($_POST['is_active']) ? (int)($_POST['is_active'] === 'on' ? 1 : $_POST['is_active']) : 1;
  } else {
    $payload = get_json_body();
  }

  $id = isset($payload['id']) ? (int)$payload['id'] : null;
  $name = trim((string)($payload['name'] ?? ''));
  $hex = isset($payload['hex']) ? trim((string)$payload['hex']) : null;
  $isActive = isset($payload['is_active']) ? (int)$payload['is_active'] : 1;

  if ($name === '') {
    json_response(422, ['error' => 'Color name is required']);
  }
  if ($hex === null || $hex === '') {
    json_response(422, ['error' => 'Color code is required']);
  }
  // Normalize hex like #AABBCC
  if ($hex[0] !== '#') { $hex = '#' . $hex; }
  if (!preg_match('/^#[0-9a-fA-F]{6}$/', $hex)) {
    json_response(422, ['error' => 'Invalid color code. Use format #RRGGBB']);
  }

  try {
    if ($id) {
      $stmt = $conn->prepare('UPDATE color_master SET ColorName = ?, HexCode = ?, IsActive = ? WHERE Id = ?');
      $stmt->bind_param('ssii', $name, $hex, $isActive, $id);
      $stmt->execute();
      $stmt->close();
      json_response(200, ['message' => 'Color updated', 'id' => $id]);
    } else {
      $newId = next_numeric_id($conn, 'color_master');
      $stmt = $conn->prepare('INSERT INTO color_master (Id, ColorName, HexCode, IsActive) VALUES (?, ?, ?, ?)');
      $stmt->bind_param('issi', $newId, $name, $hex, $isActive);
      $stmt->execute();
      $stmt->close();
      json_response(200, ['message' => 'Color created', 'id' => $newId]);
    }
  } catch (mysqli_sql_exception $e) {
    json_response(500, ['error' => 'Failed to save color', 'details' => $e->getMessage()]);
  }
  exit;
}

if ($method === 'DELETE') {
  ensure_color_table($conn);
  parse_str($_SERVER['QUERY_STRING'] ?? '', $qs);
  $id = isset($qs['id']) ? (int)$qs['id'] : 0;
  if ($id <= 0) { json_response(422, ['error' => 'id is required']); }
  try {
    $stmt = $conn->prepare('DELETE FROM color_master WHERE Id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    json_response(200, ['message' => 'Color deleted']);
  } catch (mysqli_sql_exception $e) {
    json_response(500, ['error' => 'Failed to delete color', 'details' => $e->getMessage()]);
  }
  exit;
}

json_response(405, ['error' => 'Method not allowed']);
