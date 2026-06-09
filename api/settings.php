<?php

declare(strict_types=1);

$settings = Database::fetchAll('SELECT `key`, `value` FROM settings');
$data = [];
$privateKeys = ['db_', 'smtp_password'];
foreach ($settings as $row) {
    $key = $row['key'];
    $isPrivate = false;
    foreach ($privateKeys as $prefix) {
        if (str_starts_with($key, $prefix)) {
            $isPrivate = true;
            break;
        }
    }
    if (!$isPrivate) {
        $data[$key] = $row['value'];
    }
}

jsonResponse(['settings' => $data]);
