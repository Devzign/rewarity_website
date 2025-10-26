<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');

// Enforce authentication (Bearer token or admin session)
require_auth();

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
        $summarySql = "SELECT COUNT(*) AS total_products, COALESCE(SUM(CurrentStock),0) AS total_stock, COALESCE(SUM(CurrentStock * SellingPrice),0) AS inventory_value, COALESCE(AVG(SellingPrice),0) AS average_price FROM product_master $whereSql";
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

        $listSql = "SELECT Id, ProductName, ProductCode, SellingPrice, PurchasePrice, CurrentStock, LowStockAlertLevel, Description, ImageUrl, CategoryId, IsActive, StartDate, CreatedOn, UpdatedOn FROM product_master $whereSql ORDER BY CreatedOn DESC LIMIT 500";
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
                'selling_price' => isset($row['SellingPrice']) ? (float)$row['SellingPrice'] : 0,
                'purchase_price' => isset($row['PurchasePrice']) ? (float)$row['PurchasePrice'] : 0,
                'current_stock' => (float)$row['CurrentStock'],
                'low_stock_alert_level' => isset($row['LowStockAlertLevel']) ? (float)$row['LowStockAlertLevel'] : null,
                'description' => $row['Description'] ?? null,
                'image_url' => $row['ImageUrl'] ?? null,
                'category_id' => isset($row['CategoryId']) ? (int)$row['CategoryId'] : null,
                'is_active' => (int)$row['IsActive'] === 1,
                'start_date' => $row['StartDate'] ? date('Y-m-d', strtotime($row['StartDate'])) : null,
                'created_on' => $row['CreatedOn'] ? date('Y-m-d', strtotime($row['CreatedOn'])) : null,
                'updated_on' => $row['UpdatedOn'] ? date('Y-m-d', strtotime($row['UpdatedOn'])) : null,
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
        $payload['purchase_price'] = isset($_POST['purchase_price']) ? (float)$_POST['purchase_price'] : null;
        $payload['selling_price'] = isset($_POST['selling_price']) ? (float)$_POST['selling_price'] : null;
        $payload['current_stock'] = isset($_POST['current_stock']) ? (float)$_POST['current_stock'] : null;
        $payload['low_stock_alert_level'] = isset($_POST['low_stock_alert_level']) ? (float)$_POST['low_stock_alert_level'] : null;
        $payload['start_date'] = $_POST['start_date'] ?? null;
        // Checkbox may be 'on', '1', or omitted
        $payload['is_active'] = isset($_POST['is_active']) ? (int)($_POST['is_active'] === 'on' ? 1 : $_POST['is_active']) : 1;
        $payload['description'] = $_POST['description'] ?? null;
        $payload['category_id'] = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null;
        $payload['color_id'] = isset($_POST['color_id']) && $_POST['color_id'] !== '' ? (int)$_POST['color_id'] : null;
        $payload['created_on'] = isset($_POST['created_on']) && $_POST['created_on'] !== '' ? $_POST['created_on'] : null;
    } else {
        $payload = get_json_body();
    }

    if (empty($payload['product_name']) || empty($payload['product_code'])) {
        http_response_code(422);
        echo json_encode(['error' => 'Product name and code are required.']);
        exit;
    }

    if (!isset($payload['purchase_price']) || !isset($payload['selling_price'])) {
        http_response_code(422);
        echo json_encode(['error' => 'Purchase and selling prices are required.']);
        exit;
    }

    $productName = trim((string)$payload['product_name']);
    $productCode = trim((string)$payload['product_code']);
    $purchasePrice = (float)$payload['purchase_price'];
    $sellingPrice = (float)$payload['selling_price'];
    $currentStock = isset($payload['current_stock']) ? (float)$payload['current_stock'] : 0;
    $lowStockAlert = isset($payload['low_stock_alert_level']) ? (float)$payload['low_stock_alert_level'] : null;
    $startDate = !empty($payload['start_date']) ? $payload['start_date'] : null;
    $isActive = isset($payload['is_active']) ? (int)$payload['is_active'] : 1;
    $description = isset($payload['description']) ? trim((string)$payload['description']) : null;
    $categoryId = isset($payload['category_id']) ? (int)$payload['category_id'] : null;
    $colorId = isset($payload['color_id']) ? (int)$payload['color_id'] : null;
    $createdOn = isset($payload['created_on']) && $payload['created_on'] !== '' ? (string)$payload['created_on'] : null;

    // If color selected, append to product name, using color_master
    if ($colorId) {
        try {
            $cs = $conn->prepare('SELECT ColorName FROM color_master WHERE Id = ? LIMIT 1');
            $cs->bind_param('i', $colorId);
            $cs->execute();
            $cres = $cs->get_result();
            $crow = $cres->fetch_assoc();
            $cs->close();
            if ($crow && ($crow['ColorName'] ?? '') !== '') {
                $productName = trim($productName . ' (' . $crow['ColorName'] . ')');
            } else {
                $colorId = null; // unknown color
            }
        } catch (mysqli_sql_exception $e) {
            $colorId = null;
        }
    }

    // Handle image upload if multipart
    $imageUrl = null;
    if ($isMultipart && isset($_FILES['image']) && is_array($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['image'];
        if (($file['error'] ?? 0) !== UPLOAD_ERR_OK) {
            http_response_code(422);
            echo json_encode(['error' => 'Image upload failed.']);
            exit;
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($allowed[$mime])) {
            http_response_code(422);
            echo json_encode(['error' => 'Unsupported image type. Use JPG, PNG, or WEBP.']);
            exit;
        }
        if ($file['size'] > 2 * 1024 * 1024) { // 2MB limit
            http_response_code(422);
            echo json_encode(['error' => 'Image too large. Max 2 MB.']);
            exit;
        }

        // Defer final path until after we have product id
    }

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
        // Determine if extended columns exist
        $hasImage = false; $hasCategory = false; $hasColor = false; $hasCreated = false;
        try {
            $ch1 = $conn->query("SHOW COLUMNS FROM product_master LIKE 'ImageUrl'");
            $hasImage = ($ch1 && $ch1->num_rows > 0); $ch1?->free();
            $ch2 = $conn->query("SHOW COLUMNS FROM product_master LIKE 'CategoryId'");
            $hasCategory = ($ch2 && $ch2->num_rows > 0); $ch2?->free();
            $ch3 = $conn->query("SHOW COLUMNS FROM product_master LIKE 'ColorId'");
            $hasColor = ($ch3 && $ch3->num_rows > 0); $ch3?->free();
            $ch4 = $conn->query("SHOW COLUMNS FROM product_master LIKE 'CreatedOn'");
            $hasCreated = ($ch4 && $ch4->num_rows > 0); $ch4?->free();
        } catch (mysqli_sql_exception $e) { $hasImage = $hasCategory = $hasColor = $hasCreated = false; }

        $cols = 'Id, ProductName, ProductCode, StartDate, Description, IsActive, PurchasePrice, SellingPrice, CurrentStock, LowStockAlertLevel';
        $place = '?, ?, ?, ?, ?, ?, ?, ?, ?, ?';
        $types = 'issssidddd';
        $vals = [$nextId, $productName, $productCode, ($startDate ?: null), $description, $isActive, $purchasePrice, $sellingPrice, $currentStock, ($lowStockAlert !== null ? $lowStockAlert : null)];
        if ($hasImage) { $cols .= ', ImageUrl'; $place .= ', ?'; $types .= 's'; $vals[] = null; }
        if ($hasCategory) { $cols .= ', CategoryId'; $place .= ', ?'; $types .= 'i'; $vals[] = ($categoryId ?: null); }
        if ($hasColor) { $cols .= ', ColorId'; $place .= ', ?'; $types .= 'i'; $vals[] = ($colorId ?: null); }
        if ($hasCreated && $createdOn) { $cols .= ', CreatedOn'; $place .= ', ?'; $types .= 's'; $vals[] = $createdOn; }
        $sql = 'INSERT INTO product_master (' . $cols . ') VALUES (' . $place . ')';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$vals);
        $stmt->execute();
        $stmt->close();

        // If image was provided, save file and update row
        if ($isMultipart && isset($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE && $hasImage) {
            $file = $_FILES['image'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? 'bin';
            $dir = __DIR__ . '/../uploads/products';
            if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
            $fname = 'product_' . $nextId . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $path = $dir . '/' . $fname;
            if (!move_uploaded_file($file['tmp_name'], $path)) {
                // Do not fail creation; just ignore image
            } else {
                $relUrl = '/uploads/products/' . $fname;
                $upd = $conn->prepare('UPDATE product_master SET ImageUrl = ? WHERE Id = ?');
                $upd->bind_param('si', $relUrl, $nextId);
                $upd->execute();
                $upd->close();
                $imageUrl = $relUrl;
            }
        }

        $out = ['message' => 'Product created successfully', 'product_id' => $nextId];
        if ($imageUrl) { $out['image_url'] = $imageUrl; }
        echo json_encode($out);
    } catch (mysqli_sql_exception $exception) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create product', 'details' => $exception->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
