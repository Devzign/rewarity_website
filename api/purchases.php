<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');

// Enforce authentication (Bearer token or admin session)
require_auth();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $filters = [
        'product_id' => isset($_GET['product_id']) ? (int)$_GET['product_id'] : null,
        'seller_id' => isset($_GET['seller_id']) ? (int)$_GET['seller_id'] : null,
        'start_date' => $_GET['start_date'] ?? null,
        'end_date' => $_GET['end_date'] ?? null,
    ];

    $conditions = [];
    $types = '';
    $params = [];

    if ($filters['product_id']) { $conditions[] = 'pp.ProductId = ?'; $types .= 'i'; $params[] = $filters['product_id']; }
    if ($filters['seller_id']) { $conditions[] = 'pp.SellerId = ?'; $types .= 'i'; $params[] = $filters['seller_id']; }
    if (!empty($filters['start_date'])) { $conditions[] = 'DATE(pp.PurchaseDate) >= ?'; $types .= 's'; $params[] = $filters['start_date']; }
    if (!empty($filters['end_date'])) { $conditions[] = 'DATE(pp.PurchaseDate) <= ?'; $types .= 's'; $params[] = $filters['end_date']; }

    $whereSql = $conditions ? ('WHERE ' . implode(' AND ', $conditions)) : '';

    $sql = "SELECT pp.Id, pp.ProductId, pp.SellerId, pp.Price, pp.Qty, DATE_FORMAT(pp.PurchaseDate, '%Y-%m-%d') AS PurchaseDate,
                   p.ProductName, u.UserName AS SellerName,
                   CASE WHEN EXISTS (SELECT 1 FROM information_schema.COLUMNS c WHERE c.TABLE_SCHEMA = DATABASE() AND c.TABLE_NAME = 'product_purchase_price' AND c.COLUMN_NAME = 'Notes') THEN pp.Notes ELSE NULL END AS Notes
            FROM product_purchase_price pp
            INNER JOIN product_master p ON p.Id = pp.ProductId
            INNER JOIN user_master u ON u.Id = pp.SellerId
            $whereSql
            ORDER BY pp.PurchaseDate DESC, pp.Id DESC
            LIMIT 500";

    try {
        $stmt = $conn->prepare($sql);
        if ($types) { $stmt->bind_param($types, ...$params); }
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = [
                'id' => (int)$row['Id'],
                'product_id' => (int)$row['ProductId'],
                'product_name' => $row['ProductName'],
                'seller_id' => (int)$row['SellerId'],
                'seller_name' => $row['SellerName'],
                'price' => isset($row['Price']) ? (float)$row['Price'] : 0,
                'quantity' => isset($row['Qty']) ? (float)$row['Qty'] : 0,
                'purchase_date' => $row['PurchaseDate'],
                'notes' => $row['Notes'] ?? null,
            ];
        }
        $stmt->close();
        echo json_encode(['purchases' => $rows]);
    } catch (mysqli_sql_exception $exception) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch purchases', 'details' => $exception->getMessage()]);
    }
    exit;
}

if ($method === 'POST') {
    $payload = [];
    $isMultipart = isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false;
    if ($isMultipart) {
        $payload['product_id'] = isset($_POST['product_id']) ? (int)$_POST['product_id'] : null;
        $payload['seller_id'] = isset($_POST['seller_id']) ? (int)$_POST['seller_id'] : null;
        $payload['purchase_price'] = isset($_POST['purchase_price']) ? (float)$_POST['purchase_price'] : null;
        $payload['quantity'] = isset($_POST['quantity']) ? (float)$_POST['quantity'] : null;
        $payload['purchase_date'] = $_POST['purchase_date'] ?? null;
        $payload['notes'] = isset($_POST['notes']) ? trim((string)$_POST['notes']) : null;
    } else {
        $payload = get_json_body();
    }

    if (empty($payload['product_id']) || empty($payload['seller_id']) || !isset($payload['purchase_price']) || !isset($payload['quantity']) || empty($payload['purchase_date'])) {
        http_response_code(422);
        echo json_encode(['error' => 'Product, seller, price, quantity and date are required.']);
        exit;
    }

    $productId = (int)$payload['product_id'];
    $sellerId = (int)$payload['seller_id'];
    $price = (float)$payload['purchase_price'];
    $qty = (float)$payload['quantity'];
    $purchaseDate = $payload['purchase_date'];
    $notes = isset($payload['notes']) ? trim((string)$payload['notes']) : null;

    try {
        $conn->begin_transaction();

        // Ensure product exists
        $stmt = $conn->prepare('SELECT CurrentStock FROM product_master WHERE Id = ?');
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $prodRes = $stmt->get_result();
        if ($prodRes->num_rows === 0) { throw new RuntimeException('Invalid product.'); }
        $prodRow = $prodRes->fetch_assoc();
        $currentStock = isset($prodRow['CurrentStock']) ? (float)$prodRow['CurrentStock'] : 0;
        $stmt->close();

        // Ensure seller exists
        $stmt = $conn->prepare('SELECT Id FROM user_master WHERE Id = ?');
        $stmt->bind_param('i', $sellerId);
        $stmt->execute();
        $usrRes = $stmt->get_result();
        if ($usrRes->num_rows === 0) { throw new RuntimeException('Invalid seller.'); }
        $stmt->close();

        $nextId = next_numeric_id($conn, 'product_purchase_price');

        // Verify Notes column existence to include it conditionally
        $hasNotes = false;
        $colStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_purchase_price' AND COLUMN_NAME = 'Notes'");
        $colStmt->execute();
        $colRes = $colStmt->get_result();
        if ($row = $colRes->fetch_assoc()) { $hasNotes = ((int)$row['cnt']) > 0; }
        $colStmt->close();

        if ($hasNotes) {
            $ins = $conn->prepare('INSERT INTO product_purchase_price (Id, ProductId, Price, SellerId, PurchaseDate, Qty, Notes) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $notesParam = $notes !== null ? $notes : null;
            $ins->bind_param('iidiids', $nextId, $productId, $price, $sellerId, $purchaseDate, $qty, $notesParam);
        } else {
            $ins = $conn->prepare('INSERT INTO product_purchase_price (Id, ProductId, Price, SellerId, PurchaseDate, Qty) VALUES (?, ?, ?, ?, ?, ?)');
            $ins->bind_param('iidiid', $nextId, $productId, $price, $sellerId, $purchaseDate, $qty);
        }
        $ins->execute();
        $ins->close();

        // Update product stock and purchase price
        $upd = $conn->prepare('UPDATE product_master SET CurrentStock = CurrentStock + ?, PurchasePrice = ? WHERE Id = ?');
        $upd->bind_param('ddi', $qty, $price, $productId);
        $upd->execute();
        $upd->close();

        $conn->commit();

        echo json_encode([
            'message' => 'Purchase recorded successfully',
            'purchase_id' => $nextId,
            'new_stock' => $currentStock + $qty
        ]);
    } catch (RuntimeException $e) {
        $conn->rollback();
        http_response_code(422);
        echo json_encode(['error' => $e->getMessage()]);
    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to record purchase', 'details' => $exception->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);

