<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');

// Admin endpoints; require auth
require_auth();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function ensure_category_table(mysqli $conn): void {
  // No-op here; migrations handle schema. Optionally check existence.
}

if ($method === 'GET') {
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
    echo json_encode(['categories' => $rows]);
  } catch (mysqli_sql_exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load categories', 'details' => $e->getMessage()]);
  }
  exit;
}

if ($method === 'POST') {
  $payload = [];
  $isMultipart = isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false;
  if ($isMultipart) {
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
  if ($name === '') {
    http_response_code(422);
    echo json_encode(['error' => 'Category name is required']);
    exit;
  }

  try {
    if ($id) {
      $stmt = $conn->prepare('UPDATE category_master SET CategoryName = ?, Description = ?, IsActive = ? WHERE Id = ?');
      $stmt->bind_param('ssii', $name, $desc, $isActive, $id);
      $stmt->execute();
      $stmt->close();
      echo json_encode(['message' => 'Category updated', 'id' => $id]);
    } else {
      $newId = next_numeric_id($conn, 'category_master');
      $stmt = $conn->prepare('INSERT INTO category_master (Id, CategoryName, Description, IsActive) VALUES (?, ?, ?, ?)');
      $stmt->bind_param('issi', $newId, $name, $desc, $isActive);
      $stmt->execute();
      $stmt->close();
      echo json_encode(['message' => 'Category created', 'id' => $newId]);
    }
  } catch (mysqli_sql_exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save category', 'details' => $e->getMessage()]);
  }
  exit;
}

if ($method === 'DELETE') {
  parse_str($_SERVER['QUERY_STRING'] ?? '', $qs);
  $id = isset($qs['id']) ? (int)$qs['id'] : 0;
  if ($id <= 0) { http_response_code(422); echo json_encode(['error' => 'id is required']); exit; }
  try {
    $stmt = $conn->prepare('DELETE FROM category_master WHERE Id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['message' => 'Category deleted']);
  } catch (mysqli_sql_exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to delete category', 'details' => $e->getMessage()]);
  }
  exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);

