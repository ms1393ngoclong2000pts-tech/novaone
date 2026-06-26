<?php

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (preg_match('#^/(app|config|database|scripts|storage)(/|$)#', $path)) {
    http_response_code(404);
    exit('Not found.');
}

$file = __DIR__ . $path;
if ($path !== '/' && is_file($file)) {
    return false;
}

require __DIR__ . '/public/index.php';
