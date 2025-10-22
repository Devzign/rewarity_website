<?php
// Enable strict mysqli errors as exceptions for easier debugging
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Detect environment
$isLocal = false;
if (PHP_SAPI === 'cli-server') {
    $isLocal = true; // php -S built-in server
} elseif (!empty($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
    $isLocal = true;
} elseif (!empty($_SERVER['REMOTE_ADDR']) && in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'], true)) {
    $isLocal = true;
}
$appEnv = getenv('APP_ENV') ?: ($isLocal ? 'local' : 'production');

// Defaults for local development (previous working values)
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'rewarity_web_db';

// Environment-specific overrides
if ($appEnv === 'production') {
    // Prefer environment variables on the server
    $host = getenv('DB_HOST') ?: $host;
    $user = getenv('DB_USER') ?: $user;
    $pass = getenv('DB_PASS') ?: $pass;
    $db   = getenv('DB_NAME') ?: $db;

    // Optional file-based overrides for server: includes/env.php
    $envFile = __DIR__ . '/env.php';
    if (is_file($envFile)) {
        require $envFile; // may set $host, $user, $pass, $db
    }
} else {
    // Local dev: optionally allow includes/env.local.php to override
    $localEnvFile = __DIR__ . '/env.local.php';
    if (is_file($localEnvFile)) {
        require $localEnvFile;
    }
}

// Establish Database Connection
try {
    $conn = new mysqli($host, $user, $pass, $db);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    echo '<h2>⚠️ Database connection failed</h2>';
    exit;
}
?>
