<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

/**
 * Send a JSON response and terminate execution.
 */
function json_response(int $statusCode, array $payload): void
{
  http_response_code($statusCode);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
  exit;
}

/**
 * Decode JSON request payload.
 */
function get_json_body(): array
{
  $raw = file_get_contents('php://input');
  $data = json_decode($raw, true);

  if (!is_array($data)) {
    json_response(400, ['error' => 'Invalid or missing JSON payload']);
  }

  return $data;
}

/**
 * Ensure the request method is allowed.
 */
function ensure_method(string $expected): void
{
  if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== strtoupper($expected)) {
    json_response(405, ['error' => 'Method not allowed']);
  }
}

/**
 * Ensure required keys exist in the payload.
 */
function require_keys(array $input, array $keys): void
{
  $missing = [];
  foreach ($keys as $key) {
    if (!array_key_exists($key, $input)) {
      $missing[] = $key;
    }
  }

  if (!empty($missing)) {
    json_response(422, ['error' => 'Missing required fields', 'fields' => $missing]);
  }
}

/**
 * Fetch the next numeric identifier for tables that do not auto-increment.
 */
function next_numeric_id(mysqli $conn, string $table, string $column = 'Id'): int
{
  $stmt = $conn->prepare(sprintf('SELECT COALESCE(MAX(`%s`), 0) + 1 AS next_id FROM `%s`', $column, $table));
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  $stmt->close();

  return (int)($row['next_id'] ?? 1);
}

/**
 * Convert typical truthy user input into boolean.
 */
function to_bool($value): bool
{
  if (is_bool($value)) {
    return $value;
  }

  if (is_numeric($value)) {
    return (int)$value === 1;
  }

  if (is_string($value)) {
    return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
  }

  return false;
}
