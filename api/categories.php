<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');

// Admin endpoints; require auth
require_auth();

// Unified error handling
register_api_error_handler();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function ensure_category_table(mysqli $conn): void {
  try {
    $sql = "CREATE TABLE IF NOT EXISTS `category_master` (
      `Id` INT NOT NULL,
      `CategoryName` VARCHAR(80) NOT NULL,
      `Description` VARCHAR(255) NULL,
      `IsActive` TINYINT(1) NOT NULL DEFAULT 1,
      `CreatedOn` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
      `UpdatedOn` DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`Id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->query($sql);
  } catch (mysqli_sql_exception $e) {
    // Fallback: ignore if we cannot create (permission) — callers will see actual INSERT/SELECT errors.
  }
}

if ($method === 'GET') {
    ensure_category_table($conn);
  try {
    $rows = [];
    $res = $conn->query('SELECT Id, CategoryName, Description, IsActive, CreatedOn FROM category_master ORDER BY CategoryName');
    while ($row = $res->fetch_assoc()) {
      $rows[] = [
        'id' => (int)$row['Id'],
        'name' => $row['CategoryName'],
        'description' => $row['Description'] ?? null,
        'is_active' => (int)$row['IsActive'] === 1,
        'created_on' => $row['CreatedOn'] ? date('Y-m-d H:i:s', strtotime($row['CreatedOn'])) : null,
      ];
    }
    json_response(200, ['categories' => $rows]);
  } catch (mysqli_sql_exception $e) {
    json_response(500, ['error' => 'Failed to load categories', 'details' => $e->getMessage()]);
  }
  exit;
}

if ($method === 'POST') {
    ensure_category_table($conn);
  $payload = [];
  $isMultipart = isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false;
  if ($isMultipart) {
    $payload['id'] = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : null;
    $payload['name'] = trim((string)($_POST['name'] ?? ''));
    $payload['description'] = isset($_POST['description']) ? trim((string)$_POST['description']) : null;
    $payload['is_active'] = isset($_POST['is_active']) ? (int)($_POST['is_active'] === 'on' ? 1 : $_POST['is_active']) : 1;
  } else {
    $payload = get_json_body();
  }

  $id = isset($payload['id']) ? (int)$payload['id'] : null;
  $name = trim((string)($payload['name'] ?? ''));
  $desc = isset($payload['description']) ? trim((string)$payload['description']) : null;
  $isActive = isset($payload['is_active']) ? (int)$payload['is_active'] : 1;
  if ($name === '') { json_response(422, ['error' => 'Category name is required']); }

  try {
    if ($id) {
      $stmt = $conn->prepare('UPDATE category_master SET CategoryName = ?, Description = ?, IsActive = ? WHERE Id = ?');
      $stmt->bind_param('ssii', $name, $desc, $isActive, $id);
      $stmt->execute();
      $stmt->close();
      json_response(200, ['message' => 'Category updated', 'id' => $id]);
    } else {
      $newId = next_numeric_id($conn, 'category_master');
      $stmt = $conn->prepare('INSERT INTO category_master (Id, CategoryName, Description, IsActive) VALUES (?, ?, ?, ?)');
      $stmt->bind_param('issi', $newId, $name, $desc, $isActive);
      $stmt->execute();
      $stmt->close();
      json_response(200, ['message' => 'Category created', 'id' => $newId]);
    }
  } catch (mysqli_sql_exception $e) {
    json_response(500, ['error' => 'Failed to save category', 'details' => $e->getMessage()]);
  }
  exit;
}

if ($method === 'DELETE') {
  ensure_category_table($conn);
  parse_str($_SERVER['QUERY_STRING'] ?? '', $qs);
  $id = isset($qs['id']) ? (int)$qs['id'] : 0;
  if ($id <= 0) { json_response(422, ['error' => 'id is required']); }
  try {
    $stmt = $conn->prepare('DELETE FROM category_master WHERE Id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    json_response(200, ['message' => 'Category deleted']);
  } catch (mysqli_sql_exception $e) {
    json_response(500, ['error' => 'Failed to delete category', 'details' => $e->getMessage()]);
  }
  exit;
}

json_response(405, ['error' => 'Method not allowed']);
