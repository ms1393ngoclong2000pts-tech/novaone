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

$publicFile = __DIR__ . '/public' . $path;
if ($path !== '/' && is_file($publicFile)) {
    $extension = strtolower(pathinfo($publicFile, PATHINFO_EXTENSION));
    $types = [
        'css' => 'text/css; charset=UTF-8',
        'js' => 'application/javascript; charset=UTF-8',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'webp' => 'image/webp',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
    ];

    if (! isset($types[$extension])) {
        http_response_code(404);
        exit('Not found.');
    }

    header('Content-Type: ' . $types[$extension]);
    header('Content-Length: ' . filesize($publicFile));
    readfile($publicFile);
    return true;
}

if (preg_match('#^/(assets|images|css|js|fonts)(/|$)#', $path)) {
    http_response_code(404);
    exit('Not found.');
}

require __DIR__ . '/public/index.php';
