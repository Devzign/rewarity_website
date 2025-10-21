<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$includeAdmin = (isset($_GET['include_admin']) && to_bool($_GET['include_admin'])) ? true : false;

try {
    $query = 'SELECT id, typename, description FROM user_type';
    if (!$includeAdmin) {
        $query .= " WHERE UPPER(typename) NOT IN ('SUPER_ADMIN','ADMIN')";
    }
    $query .= ' ORDER BY typename';

    $types = [];
    if ($result = $conn->query($query)) {
        while ($row = $result->fetch_assoc()) {
            $types[] = [
                'id' => (int)$row['id'],
                'name' => $row['typename'],
                'description' => $row['description'] ?? null,
            ];
        }
        $result->free();
    }

    echo json_encode(['user_types' => $types]);
} catch (mysqli_sql_exception $exception) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch user types', 'details' => $exception->getMessage()]);
}
