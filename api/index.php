<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

if (!isInstalled()) {
    jsonResponse(['error' => 'Site not installed'], 503);
}

$path = $_GET['route'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (str_contains($contentType, 'multipart/form-data')) {
    $body = $_POST;
} else {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
}

I18n::init($_GET['lang'] ?? null);

$routes = [
    'GET:map'           => 'api/map.php',
    'GET:donations'     => 'api/donations.php',
    'POST:donations'    => 'api/donations.php',
    'GET:organizations' => 'api/organizations.php',
    'POST:organizations'=> 'api/organizations.php',
    'GET:my/listings'   => 'api/my-listings.php',
    'PUT:my/listings'   => 'api/my-listings.php',
    'PATCH:my/listings' => 'api/my-listings.php',
    'DELETE:my/listings'=> 'api/my-listings.php',
    'POST:auth/login'   => 'api/auth.php',
    'POST:auth/register'=> 'api/auth.php',
    'POST:auth/logout'  => 'api/auth.php',
    'GET:auth/me'       => 'api/auth.php',
    'POST:auth/forgot'  => 'api/auth.php',
    'POST:auth/reset'   => 'api/auth.php',
    'POST:listing-inquiry' => 'api/listing-inquiry.php',
    'GET:settings'      => 'api/settings.php',
    'GET:lang'          => 'api/lang.php',
    'POST:reservations' => 'api/reservations.php',
    'GET:admin/settings'  => 'api/admin/settings.php',
    'POST:admin/settings' => 'api/admin/settings.php',
    'POST:admin/test-email' => 'api/admin/test-email.php',
    'POST:admin/test_email' => 'api/admin/test-email.php',
    'GET:admin/users'     => 'api/admin/users.php',
    'PUT:admin/users'     => 'api/admin/users.php',
    'DELETE:admin/users'  => 'api/admin/users.php',
];

$key = $method . ':' . $path;
if (!isset($routes[$key])) {
    jsonResponse(['error' => 'Not found'], 404);
}

require ROOT_PATH . '/' . $routes[$key];
