<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $filters = [
        'product_id' => isset($_GET['product_id']) ? (int)$_GET['product_id'] : null,
        'dealer_id' => isset($_GET['dealer_id']) ? (int)$_GET['dealer_id'] : null,
        'distributor_id' => isset($_GET['distributor_id']) ? (int)$_GET['distributor_id'] : null,
        'salesperson_id' => isset($_GET['salesperson_id']) ? (int)$_GET['salesperson_id'] : null,
        'start_date' => $_GET['start_date'] ?? null,
        'end_date' => $_GET['end_date'] ?? null,
    ];

    $conditions = [];
    $types = '';
    $params = [];

    if ($filters['product_id']) {
        $conditions[] = 'oi.ProductId = ?';
        $types .= 'i';
        $params[] = $filters['product_id'];
    }
    if ($filters['dealer_id']) {
        $conditions[] = 'o.DealerId = ?';
        $types .= 'i';
        $params[] = $filters['dealer_id'];
    }
    if ($filters['distributor_id']) {
        $conditions[] = 'o.DistributorId = ?';
        $types .= 'i';
        $params[] = $filters['distributor_id'];
    }
    if ($filters['salesperson_id']) {
        $conditions[] = 'o.SalesPersonId = ?';
        $types .= 'i';
        $params[] = $filters['salesperson_id'];
    }
    if (!empty($filters['start_date'])) {
        $conditions[] = 'DATE(o.OrderDate) >= ?';
        $types .= 's';
        $params[] = $filters['start_date'];
    }
    if (!empty($filters['end_date'])) {
        $conditions[] = 'DATE(o.OrderDate) <= ?';
        $types .= 's';
        $params[] = $filters['end_date'];
    }

    $whereSql = '';
    if ($conditions) {
        $whereSql = 'WHERE ' . implode(' AND ', $conditions);
    }

    $summarySql = "SELECT COUNT(DISTINCT o.Id) AS total_orders, COALESCE(SUM(oi.Quantity),0) AS total_quantity, COALESCE(SUM(o.TotalAmount),0) AS total_amount
                   FROM order_master o
                   INNER JOIN order_items oi ON oi.OrderId = o.Id
                   $whereSql";

    $ordersSql = "SELECT o.Id,
                         o.OrderNumber,
                         DATE_FORMAT(o.OrderDate, '%Y-%m-%d') AS OrderDate,
                         o.TotalAmount,
                         o.Notes,
                         o.AttachmentPath,
                         d.UserName AS DealerName,
                         dist.UserName AS DistributorName,
                         sp.UserName AS SalespersonName,
                         oi.Quantity,
                         oi.UnitPrice,
                         oi.TotalAmount AS ItemTotal,
                         p.ProductName
                  FROM order_master o
                  INNER JOIN user_master d ON d.Id = o.DealerId
                  INNER JOIN user_master dist ON dist.Id = o.DistributorId
                  INNER JOIN user_master sp ON sp.Id = o.SalesPersonId
                  INNER JOIN order_items oi ON oi.OrderId = o.Id
                  INNER JOIN product_master p ON p.Id = oi.ProductId
                  $whereSql
                  ORDER BY o.OrderDate DESC, o.Id DESC
                  LIMIT 500";

    $summary = ['total_orders' => 0, 'total_quantity' => 0, 'total_amount' => 0, 'average_amount' => 0];
    $orders = [];

    try {
        $stmt = $conn->prepare($summarySql);
        if ($types) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $summary['total_orders'] = (int)$row['total_orders'];
            $summary['total_quantity'] = (float)$row['total_quantity'];
            $summary['total_amount'] = (float)$row['total_amount'];
            $summary['average_amount'] = $summary['total_orders'] > 0 ? ($summary['total_amount'] / $summary['total_orders']) : 0;
        }
        $stmt->close();

        $stmt = $conn->prepare($ordersSql);
        if ($types) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $orders[] = [
                'id' => (int)$row['Id'],
                'order_number' => $row['OrderNumber'],
                'order_date' => $row['OrderDate'],
                'total_amount' => (float)$row['TotalAmount'],
                'notes' => $row['Notes'],
                'attachment' => $row['AttachmentPath'],
                'attachment_url' => $row['AttachmentPath'] ? '/'.$row['AttachmentPath'] : null,
                'dealer_name' => $row['DealerName'],
                'distributor_name' => $row['DistributorName'],
                'salesperson_name' => $row['SalespersonName'],
                'product_name' => $row['ProductName'],
                'quantity' => (float)$row['Quantity'],
                'unit_price' => (float)$row['UnitPrice'],
                'item_total' => (float)$row['ItemTotal'],
            ];
        }
        $stmt->close();

        echo json_encode(['summary' => $summary, 'orders' => $orders]);
    } catch (mysqli_sql_exception $exception) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch orders', 'details' => $exception->getMessage()]);
    }
    exit;
}

if ($method === 'POST') {
    $isMultipart = isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false;
    $payload = [];

    if ($isMultipart) {
        $payload['dealer_id'] = isset($_POST['dealer_id']) ? (int)$_POST['dealer_id'] : null;
        $payload['distributor_id'] = isset($_POST['distributor_id']) ? (int)$_POST['distributor_id'] : null;
        $payload['salesperson_id'] = isset($_POST['salesperson_id']) ? (int)$_POST['salesperson_id'] : null;
        $payload['product_id'] = isset($_POST['product_id']) ? (int)$_POST['product_id'] : null;
        $payload['quantity'] = isset($_POST['quantity']) ? (float)$_POST['quantity'] : null;
        $payload['unit_price'] = isset($_POST['unit_price']) ? (float)$_POST['unit_price'] : null;
        $payload['order_date'] = $_POST['order_date'] ?? null;
        $payload['notes'] = $_POST['notes'] ?? null;
        $payload['total_amount'] = isset($_POST['total_amount']) ? (float)$_POST['total_amount'] : null;
        $payload['created_by_user_id'] = null;
        $attachmentFile = $_FILES['attachment'] ?? null;
        $attachmentBase64 = null;
        $attachmentName = null;
    } else {
        $payload = get_json_body();
        $attachmentBase64 = $payload['attachment_base64'] ?? null;
        $attachmentName = $payload['attachment_name'] ?? null;
        $attachmentFile = null;
    }

    $required = ['dealer_id','distributor_id','salesperson_id','product_id','quantity','unit_price'];
    foreach ($required as $field) {
        if (empty($payload[$field])) {
            http_response_code(422);
            echo json_encode(['error' => "Missing field: {$field}"]);
            exit;
        }
    }

    $orderDate = $payload['order_date'] ?? date('Y-m-d');
    if (!strtotime($orderDate)) {
        http_response_code(422);
        echo json_encode(['error' => 'Invalid order date provided.']);
        exit;
    }

    $quantity = (float)$payload['quantity'];
    $unitPrice = (float)$payload['unit_price'];
    $totalAmount = $payload['total_amount'] !== null ? (float)$payload['total_amount'] : $quantity * $unitPrice;
    $notes = $payload['notes'] ?? null;

    $createdBy = null;
    if (isset($_SESSION['admin_id'])) {
        $createdBy = (int)$_SESSION['admin_id'];
    } elseif (!empty($payload['created_by_user_id'])) {
        $createdBy = (int)$payload['created_by_user_id'];
    }

    if (!$createdBy) {
        http_response_code(401);
        echo json_encode(['error' => 'Creator user id is required.']);
        exit;
    }

    try {
        $conn->begin_transaction();

        // Validate user roles
        $roleStmt = $conn->prepare('SELECT ut.typename FROM user_master u INNER JOIN user_type ut ON ut.id = u.UserTypeId WHERE u.Id = ? LIMIT 1');

        // Dealer
        $roleStmt->bind_param('i', $payload['dealer_id']);
        $roleStmt->execute();
        $roleResult = $roleStmt->get_result();
        $roleRow = $roleResult->fetch_assoc();
        $role = $roleRow['typename'] ?? null;
        if (strtoupper($role ?? '') !== 'DEALER') {
            throw new RuntimeException('Selected dealer is not valid.');
        }

        // Distributor
        $roleStmt->bind_param('i', $payload['distributor_id']);
        $roleStmt->execute();
        $roleResult = $roleStmt->get_result();
        $roleRow = $roleResult->fetch_assoc();
        $role = $roleRow['typename'] ?? null;
        if (strtoupper($role ?? '') !== 'DISTRIBUTOR') {
            throw new RuntimeException('Selected distributor is not valid.');
        }

        // Salesperson
        $roleStmt->bind_param('i', $payload['salesperson_id']);
        $roleStmt->execute();
        $roleResult = $roleStmt->get_result();
        $roleRow = $roleResult->fetch_assoc();
        $role = $roleRow['typename'] ?? null;
        if (strtoupper($role ?? '') !== 'SALESPERSON') {
            throw new RuntimeException('Selected salesperson is not valid.');
        }

        // Created by role (admin / dealer / salesperson allowed)
        $roleStmt->bind_param('i', $createdBy);
        $roleStmt->execute();
        $roleResult = $roleStmt->get_result();
        $roleRow = $roleResult->fetch_assoc();
        $creatorRole = strtoupper($roleRow['typename'] ?? '');
        if (!in_array($creatorRole, ['SUPER_ADMIN','ADMIN','EMPLOYEE','DEALER','SALESPERSON'], true)) {
            throw new RuntimeException('User is not permitted to create orders.');
        }
        $roleStmt->close();

        $orderIdNext = next_numeric_id($conn, 'order_master');
        $orderNumber = sprintf('ORD-%s-%05d', date('Ymd'), $orderIdNext);

        $attachmentPath = null;
        if ($attachmentFile && $attachmentFile['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/orders';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }
            $ext = pathinfo($attachmentFile['name'], PATHINFO_EXTENSION);
            $filename = uniqid('order_', true) . ($ext ? '.' . $ext : '');
            $destination = $uploadDir . '/' . $filename;
            if (!move_uploaded_file($attachmentFile['tmp_name'], $destination)) {
                throw new RuntimeException('Failed to move uploaded file.');
            }
            $attachmentPath = 'uploads/orders/' . $filename;
        } elseif ($attachmentBase64) {
            $uploadDir = __DIR__ . '/../uploads/orders';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }
            $filename = uniqid('order_', true);
            if ($attachmentName) {
                $ext = pathinfo($attachmentName, PATHINFO_EXTENSION);
                if ($ext) {
                    $filename .= '.' . $ext;
                }
            }
            $data = base64_decode($attachmentBase64);
            if ($data === false) {
                throw new RuntimeException('Invalid attachment payload.');
            }
            $destination = $uploadDir . '/' . $filename;
            file_put_contents($destination, $data);
            $attachmentPath = 'uploads/orders/' . $filename;
        }

        $orderStmt = $conn->prepare('INSERT INTO order_master (OrderNumber, DealerId, DistributorId, SalesPersonId, CreatedByUserId, TotalAmount, Notes, AttachmentPath, OrderDate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $notesParam = $notes !== null ? $notes : null;
        $orderStmt->bind_param(
            'siiiidsss',
            $orderNumber,
            $payload['dealer_id'],
            $payload['distributor_id'],
            $payload['salesperson_id'],
            $createdBy,
            $totalAmount,
            $notesParam,
            $attachmentPath,
            $orderDate
        );
        $orderStmt->execute();
        $orderId = $orderStmt->insert_id;
        $orderStmt->close();

        $itemStmt = $conn->prepare('INSERT INTO order_items (OrderId, ProductId, Quantity, UnitPrice, TotalAmount) VALUES (?, ?, ?, ?, ?)');
        $itemStmt->bind_param('iiddd', $orderId, $payload['product_id'], $quantity, $unitPrice, $totalAmount);
        $itemStmt->execute();
        $itemStmt->close();

        $conn->commit();

        echo json_encode(['message' => 'Order created successfully', 'order_id' => $orderId, 'order_number' => $orderNumber]);
    } catch (RuntimeException $e) {
        $conn->rollback();
        http_response_code(422);
        echo json_encode(['error' => $e->getMessage()]);
    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create order', 'details' => $exception->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
