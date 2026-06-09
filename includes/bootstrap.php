<?php

declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
define('CONFIG_PATH', ROOT_PATH . '/config/config.php');

if (!file_exists(CONFIG_PATH)) {
    if (php_sapi_name() !== 'cli') {
        $installUrl = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
        $installUrl = str_replace('/api', '', $installUrl);
        $installUrl = str_replace('/install', '', $installUrl);
        header('Location: ' . $installUrl . '/install/');
        exit;
    }
    throw new RuntimeException('Configuration not found. Run the install wizard.');
}

$config = require CONFIG_PATH;

date_default_timezone_set($config['app']['timezone'] ?? 'UTC');

if (!($config['app']['debug'] ?? false)) {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
}

require_once ROOT_PATH . '/includes/Database.php';
require_once ROOT_PATH . '/includes/helpers.php';
require_once ROOT_PATH . '/includes/I18n.php';
require_once ROOT_PATH . '/includes/Auth.php';
require_once ROOT_PATH . '/includes/Mailer.php';
require_once ROOT_PATH . '/includes/EmailTemplates.php';

Database::init($config['db']);
ensureUserBlockedColumn();
ensureEnglishDefaultLanguage();
Auth::init($config);
