<?php

declare(strict_types=1);

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');

$isHttps = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_start();

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/Core/helpers.php';
load_env_file(BASE_PATH . '/.env');
require BASE_PATH . '/app/Core/View.php';
require BASE_PATH . '/app/Core/DataStore.php';
require BASE_PATH . '/app/Core/XlsxReader.php';
require BASE_PATH . '/app/Controllers/AuthController.php';
require BASE_PATH . '/app/Controllers/DashboardController.php';
require BASE_PATH . '/app/Controllers/FeatureHubController.php';
require BASE_PATH . '/app/Controllers/ResourceController.php';
require BASE_PATH . '/app/Controllers/SystemInfoController.php';
require BASE_PATH . '/app/Controllers/ReportController.php';
require BASE_PATH . '/app/Controllers/SearchController.php';
require BASE_PATH . '/app/Controllers/NotificationController.php';
require BASE_PATH . '/app/Controllers/PermissionController.php';
require BASE_PATH . '/app/Controllers/CallController.php';
require BASE_PATH . '/app/Controllers/ActivityLogController.php';
require BASE_PATH . '/app/Controllers/UserController.php';
require BASE_PATH . '/app/Controllers/ApiController.php';
require BASE_PATH . '/app/Modules/HumanResources/bootstrap.php';
require BASE_PATH . '/app/Modules/Work/bootstrap.php';
require BASE_PATH . '/app/Modules/Business/bootstrap.php';
require BASE_PATH . '/app/Modules/Inventory/bootstrap.php';
require BASE_PATH . '/app/Modules/Recruitment/bootstrap.php';

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data: blob:; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; font-src 'self' data:; object-src 'none'; base-uri 'self'; frame-ancestors 'self'; form-action 'self'");

$config = require BASE_PATH . '/config/app.php';
if (! empty($config['debug'])) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}
$store = new DataStore(BASE_PATH . '/storage/data.json', BASE_PATH . '/storage/seed.php');
