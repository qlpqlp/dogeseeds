<?php

declare(strict_types=1);

Auth::requireRole('admin');

$groups = require ROOT_PATH . '/includes/admin_settings.php';

function adminEditableKeys(array $groups): array
{
    $keys = [];
    foreach ($groups as $fields) {
        foreach ($fields as $key => $meta) {
            if (($meta['type'] ?? '') !== 'secret') {
                $keys[] = $key;
            }
        }
    }
    return $keys;
}

function adminValidateSetting(string $key, mixed $value, array $meta): ?string
{
    $type = $meta['type'] ?? 'text';

    if ($type === 'bool') {
        return null;
    }

    $str = trim((string) $value);

    if ($key === 'site_name' && $str === '') {
        return 'Site name is required';
    }

    if ($type === 'language') {
        if (!in_array($str, I18n::availableCodes(), true)) {
            return 'Invalid default language';
        }
        return null;
    }

    if ($type === 'email' && $str !== '' && !filter_var($str, FILTER_VALIDATE_EMAIL)) {
        return 'Invalid from email address';
    }

    if ($type === 'url' && $str !== '' && !filter_var($str, FILTER_VALIDATE_URL)) {
        return 'Invalid site URL';
    }

    if (in_array($type, ['int', 'float'], true) && $str !== '') {
        if (!is_numeric($str)) {
            return "Invalid value for {$key}";
        }
        $num = (float) $str;
        if (isset($meta['min']) && $num < $meta['min']) {
            return "Value for {$key} is too low";
        }
        if (isset($meta['max']) && $num > $meta['max']) {
            return "Value for {$key} is too high";
        }
    }

    if ($type === 'select') {
        $options = $meta['options'] ?? [];
        if ($str !== '' && !in_array($str, $options, true)) {
            return "Invalid value for {$key}";
        }
    }

    return null;
}

function adminNormalizeSetting(string $key, mixed $value, array $meta): string
{
    $type = $meta['type'] ?? 'text';

    if ($type === 'bool') {
        return !empty($value) ? '1' : '0';
    }

    if ($type === 'int') {
        return (string) (int) $value;
    }

    if ($type === 'float') {
        return (string) (float) $value;
    }

    return trim((string) $value);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $data = [];
    foreach ($groups as $fields) {
        foreach ($fields as $key => $meta) {
            if (($meta['type'] ?? '') === 'secret') {
                $data['smtp_password_set'] = (getSetting('smtp_password', '') ?? '') !== '';
                continue;
            }
            $value = getSetting($key, $meta['default'] ?? '') ?? ($meta['default'] ?? '');
            if (($meta['type'] ?? '') === 'bool') {
                $data[$key] = $value === '1';
            } else {
                $data[$key] = $value;
            }
        }
    }
    jsonResponse(['settings' => $data]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($groups as $fields) {
        foreach ($fields as $key => $meta) {
            $type = $meta['type'] ?? 'text';

            if ($type === 'secret') {
                if (!empty($body[$key])) {
                    setSetting($key, (string) $body[$key]);
                }
                continue;
            }

            if (!array_key_exists($key, $body) && $type !== 'bool') {
                continue;
            }

            $raw = $type === 'bool' ? $body[$key] ?? false : $body[$key];
            $error = adminValidateSetting($key, $raw, $meta);
            if ($error) {
                jsonResponse(['error' => $error], 400);
            }

            setSetting($key, adminNormalizeSetting($key, $raw, $meta));
        }
    }

    logActivity((int) Auth::user()['id'], 'update_admin_settings');
    jsonResponse(['message' => 'Settings saved']);
}

jsonResponse(['error' => 'Method not allowed'], 405);
