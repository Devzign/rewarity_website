<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');

// Enforce authentication (Bearer token or admin session)
require_auth();

function fetchUsers(mysqli $conn, array $userTypeNames): array
{
    // Compare type names case-insensitively and only return active users
    $placeholders = implode(',', array_fill(0, count($userTypeNames), '?'));
    $query = "SELECT u.Id, u.UserName, ut.typename
              FROM user_master u
              INNER JOIN user_type ut ON ut.id = u.UserTypeId
              WHERE UPPER(ut.typename) IN ($placeholders)
              ORDER BY u.UserName";

    // Ensure all bound values are uppercase
    $upper = array_map(function($t){ return strtoupper($t); }, $userTypeNames);
    $stmt = $conn->prepare($query);
    $stmt->bind_param(str_repeat('s', count($upper)), ...$upper);
    $stmt->execute();
    $result = $stmt->get_result();

    $grouped = [];
    while ($row = $result->fetch_assoc()) {
        $grouped[strtoupper($row['typename'])][] = [
            'id' => (int)$row['Id'],
            'name' => $row['UserName']
        ];
    }

    $stmt->close();

    return $grouped;
}

try {
    $userGroups = fetchUsers($conn, ['DEALER', 'DISTRIBUTOR', 'SALESPERSON']);

    $products = [];
    if ($result = $conn->query('SELECT Id, ProductName, SellingPrice AS UnitPrice, CurrentStock FROM product_master ORDER BY ProductName')) {
        while ($row = $result->fetch_assoc()) {
            $products[] = [
                'id' => (int)$row['Id'],
                'name' => $row['ProductName'],
                'unit_price' => isset($row['UnitPrice']) ? (float)$row['UnitPrice'] : 0,
                'current_stock' => isset($row['CurrentStock']) ? (float)$row['CurrentStock'] : 0,
            ];
        }
        $result->free();
    }

    echo json_encode([
        'dealers' => $userGroups['DEALER'] ?? [],
        'distributors' => $userGroups['DISTRIBUTOR'] ?? [],
        'salespersons' => $userGroups['SALESPERSON'] ?? [],
        'products' => $products,
    ]);
} catch (mysqli_sql_exception $exception) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load supporting data', 'details' => $exception->getMessage()]);
}
