<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $search = trim((string)($_GET['search'] ?? ''));
    $status = strtolower((string)($_GET['status'] ?? ''));
    $minStock = isset($_GET['min_stock']) ? (float)$_GET['min_stock'] : null;

    $conditions = [];
    $types = '';
    $params = [];

    if ($search !== '') {
        $conditions[] = '(ProductName LIKE ? OR ProductCode LIKE ?)';
        $types .= 'ss';
        $like = "%{$search}%";
        $params[] = $like;
        $params[] = $like;
    }

    if ($status === 'active') {
        $conditions[] = 'IsActive = 1';
    } elseif ($status === 'inactive') {
        $conditions[] = 'IsActive = 0';
    }

    if ($minStock !== null) {
        $conditions[] = 'CurrentStock >= ?';
        $types .= 'd';
        $params[] = $minStock;
    }

    $whereSql = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

    $summary = [
        'total_products' => 0,
        'total_stock' => 0,
        'inventory_value' => 0,
        'average_price' => 0,
    ];
    $products = [];

    try {
        $summarySql = "SELECT COUNT(*) AS total_products, COALESCE(SUM(CurrentStock),0) AS total_stock, COALESCE(SUM(CurrentStock * UnitPrice),0) AS inventory_value, COALESCE(AVG(UnitPrice),0) AS average_price FROM product_master $whereSql";
        $stmt = $conn->prepare($summarySql);
        if ($types) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $summary['total_products'] = (int)$row['total_products'];
            $summary['total_stock'] = (float)$row['total_stock'];
            $summary['inventory_value'] = (float)$row['inventory_value'];
            $summary['average_price'] = (float)$row['average_price'];
        }
        $stmt->close();

        $listSql = "SELECT Id, ProductName, ProductCode, UnitPrice, CurrentStock, IsActive, StartDate, CreatedOn FROM product_master $whereSql ORDER BY CreatedOn DESC LIMIT 500";
        $stmt = $conn->prepare($listSql);
        if ($types) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $products[] = [
                'id' => (int)$row['Id'],
                'product_name' => $row['ProductName'],
                'product_code' => $row['ProductCode'],
                'unit_price' => (float)$row['UnitPrice'],
                'current_stock' => (float)$row['CurrentStock'],
                'is_active' => (int)$row['IsActive'] === 1,
                'start_date' => $row['StartDate'] ? date('Y-m-d', strtotime($row['StartDate'])) : null,
                'created_on' => $row['CreatedOn'] ? date('Y-m-d H:i:s', strtotime($row['CreatedOn'])) : null,
            ];
        }
        $stmt->close();

        echo json_encode(['summary' => $summary, 'products' => $products]);
    } catch (mysqli_sql_exception $exception) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch products', 'details' => $exception->getMessage()]);
    }
    exit;
}

if ($method === 'POST') {
    $payload = [];
    $isMultipart = isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false;
    if ($isMultipart) {
        $payload['product_name'] = trim((string)($_POST['product_name'] ?? ''));
        $payload['product_code'] = trim((string)($_POST['product_code'] ?? ''));
        $payload['unit_price'] = isset($_POST['unit_price']) ? (float)$_POST['unit_price'] : null;
        $payload['current_stock'] = isset($_POST['current_stock']) ? (float)$_POST['current_stock'] : null;
        $payload['start_date'] = $_POST['start_date'] ?? null;
        $payload['is_active'] = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
        $payload['notes'] = $_POST['notes'] ?? null;
    } else {
        $payload = get_json_body();
    }

    if (empty($payload['product_name']) || empty($payload['product_code'])) {
        http_response_code(422);
        echo json_encode(['error' => 'Product name and code are required.']);
        exit;
    }

    if (!isset($payload['unit_price']) || !isset($payload['current_stock'])) {
        http_response_code(422);
        echo json_encode(['error' => 'Unit price and stock are required.']);
        exit;
    }

    $productName = trim((string)$payload['product_name']);
    $productCode = trim((string)$payload['product_code']);
    $unitPrice = (float)$payload['unit_price'];
    $currentStock = (float)$payload['current_stock'];
    $startDate = !empty($payload['start_date']) ? $payload['start_date'] : null;
    $isActive = isset($payload['is_active']) ? (int)$payload['is_active'] : 1;

    try {
        $checkStmt = $conn->prepare('SELECT Id FROM product_master WHERE ProductCode = ? LIMIT 1');
        $checkStmt->bind_param('s', $productCode);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows > 0) {
            $checkStmt->close();
            http_response_code(409);
            echo json_encode(['error' => 'Product code already exists.']);
            exit;
        }
        $checkStmt->close();

        $nextId = next_numeric_id($conn, 'product_master');
        $stmt = $conn->prepare('INSERT INTO product_master (Id, ProductName, ProductCode, StartDate, IsActive, UnitPrice, CurrentStock) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $startDateParam = $startDate ?: null;
        $stmt->bind_param('isssidd', $nextId, $productName, $productCode, $startDateParam, $isActive, $unitPrice, $currentStock);
        $stmt->execute();
        $stmt->close();

        echo json_encode(['message' => 'Product created successfully', 'product_id' => $nextId]);
    } catch (mysqli_sql_exception $exception) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create product', 'details' => $exception->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
