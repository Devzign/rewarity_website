<?php
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$filePath = __DIR__ . $requestUri;

if ($requestUri === '/' || $requestUri === '') {
  require __DIR__ . '/index.php';
  return;
}

if (is_file($filePath)) {
  return false;
}

require __DIR__ . '/index.php';
