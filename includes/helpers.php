<?php

declare(strict_types=1);

function jsonResponse(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function getSetting(string $key, ?string $default = null): ?string
{
    $row = Database::fetch('SELECT `value` FROM settings WHERE `key` = ?', [$key]);
    return $row ? $row['value'] : $default;
}

function ensureEnglishDefaultLanguage(): void
{
    try {
        if (getSetting('legacy_pt_default_fixed') === '1') {
            return;
        }
        if (getSetting('default_language') === 'pt') {
            setSetting('default_language', 'en');
        }
        setSetting('legacy_pt_default_fixed', '1');
    } catch (Throwable) {
        // Database may not be ready during install
    }
}

function setSetting(string $key, string $value): void
{
    Database::query(
        'INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)',
        [$key, $value]
    );
}

function logActivity(?int $userId, string $action, ?string $entityType = null, ?int $entityId = null, ?array $details = null): void
{
    Database::insert('activity_logs', [
        'user_id'     => $userId,
        'action'      => $action,
        'entity_type' => $entityType,
        'entity_id'   => $entityId,
        'details'     => $details ? json_encode($details) : null,
        'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
}

function sanitize(string $value): string
{
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

function donationTitleForCategory(string $category): string
{
    return match ($category) {
        'food'        => 'Food to share',
        'clothing'    => 'Clothing to donate',
        'toys'        => 'Toys to donate',
        'essentials'  => 'Essentials to share',
        default       => 'Items to share',
    };
}

function orgTypeIcon(string $type): string
{
    return match ($type) {
        'person'      => 'person',
        'donor'       => 'redeem',
        'supermarket' => 'store',
        'grocery'     => 'shopping_basket',
        'restaurant'  => 'restaurant',
        'cafe'        => 'local_cafe',
        'farmer'      => 'agriculture',
        'fisherman'   => 'set_meal',
        'ngo'         => 'volunteer_activism',
        'scout'       => 'hiking',
        'volunteer'   => 'groups',
        default       => 'place',
    };
}

function orgTypeColor(string $type): string
{
    return match ($type) {
        'person'      => '#4CAF50',
        'donor'       => '#4CAF50',
        'supermarket' => '#42A5F5',
        'grocery'     => '#42A5F5',
        'restaurant'  => '#FF9800',
        'cafe'        => '#FF9800',
        'farmer'      => '#689F38',
        'fisherman'   => '#26A69A',
        'ngo'         => '#EC407A',
        'scout'       => '#7E57C2',
        'volunteer'   => '#FF9800',
        default       => '#5a6a7a',
    };
}

function validCategories(): array
{
    return ['food', 'clothing', 'toys', 'essentials'];
}

function parseCategories(?array $input): array
{
    if (!$input) {
        return [];
    }
    return array_values(array_intersect($input, validCategories()));
}

function categoryIcon(string $category): string
{
    return match ($category) {
        'food'        => 'restaurant',
        'clothing'    => 'checkroom',
        'toys'        => 'toys',
        'essentials'  => 'medical_services',
        default       => 'inventory_2',
    };
}

function siteUrl(): string
{
    $configured = trim(getSetting('site_url', '') ?? '');
    if ($configured !== '') {
        return rtrim($configured, '/');
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $base = rtrim(str_replace('\\', '/', dirname($script)), '/');
    if (str_ends_with($base, '/admin') || str_ends_with($base, '/api') || str_ends_with($base, '/install')) {
        $base = dirname($base);
    }
    $base = $base === '/' ? '' : $base;

    return $scheme . '://' . $host . $base;
}

function webPath(string $path = ''): string
{
    $base = parse_url(siteUrl(), PHP_URL_PATH);
    $base = is_string($base) ? rtrim($base, '/') : '';
    $path = ltrim($path, '/');

    if ($path === '') {
        return $base === '' ? '/' : $base . '/';
    }

    return ($base === '' ? '' : $base) . '/' . $path;
}

function publicPath(?string $path): ?string
{
    if ($path === null || trim($path) === '') {
        return null;
    }

    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    return webPath($path);
}

function parsePickupDatetime(?string $value): ?string
{
    if (!$value || !is_string($value)) {
        return null;
    }
    $value = trim(str_replace('T', ' ', $value));
    $ts = strtotime($value);
    return $ts ? date('Y-m-d H:i:s', $ts) : null;
}

function validatePickupWindow(?string $start, ?string $end): ?string
{
    $pickupStart = parsePickupDatetime($start);
    $pickupEnd = parsePickupDatetime($end);

    if (!$pickupStart || !$pickupEnd) {
        return 'Pickup start and end dates are required';
    }
    if (strtotime($pickupEnd) <= strtotime($pickupStart)) {
        return 'Pickup end must be after pickup start';
    }

    return null;
}

function isInstalled(): bool
{
    if (!file_exists(CONFIG_PATH)) {
        return false;
    }
    try {
        return getSetting('site_installed') === '1';
    } catch (Throwable) {
        return false;
    }
}

function slugify(string $text): string
{
    $text = trim($text);
    if ($text === '') {
        return 'listing';
    }

    if (function_exists('iconv')) {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if ($converted !== false) {
            $text = $converted;
        }
    }

    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');

    if ($text === '') {
        return 'listing';
    }

    return substr($text, 0, 100);
}

function locationSlugBase(int $userId, string $name): string
{
    $label = trim($name) ?: 'listing';
    return $userId . '-' . slugify($label);
}

function uniqueLocationSlug(string $base, ?int $excludeId = null): string
{
    $slug = slugify($base);
    $candidate = $slug;
    $suffix = 2;

    while (true) {
        $params = [$candidate];
        $sql = 'SELECT id FROM locations WHERE slug = ?';
        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }
        $existing = Database::fetch($sql, $params);
        if (!$existing) {
            return $candidate;
        }
        $candidate = $slug . '-' . $suffix;
        $suffix++;
    }
}

function ensureLocationSlugs(): void
{
    try {
        $rows = Database::fetchAll(
            'SELECT l.id, l.name, l.slug, o.name AS org_name, o.user_id
             FROM locations l
             JOIN organizations o ON o.id = l.organization_id'
        );
    } catch (Throwable) {
        return;
    }

    foreach ($rows as $row) {
        $slug = trim($row['slug'] ?? '');
        if ($slug !== '' && preg_match('/^\d+-/', $slug)) {
            continue;
        }

        $name = trim($row['name']) ?: trim($row['org_name']) ?: 'listing';
        $base = locationSlugBase((int) $row['user_id'], $name);
        Database::query(
            'UPDATE locations SET slug = ? WHERE id = ?',
            [uniqueLocationSlug($base, (int) $row['id']), (int) $row['id']]
        );
    }
}

function absoluteUrl(?string $path = null): string
{
    if ($path === null || trim($path) === '') {
        $path = 'assets/img/DogeSeeds_card.png';
    }

    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    return rtrim(siteUrl(), '/') . webPath($path);
}

function fetchListingBySlug(string $slug): ?array
{
    return Database::fetch(
        'SELECT l.id AS location_id, l.name AS location_name, l.slug, l.image_path,
                l.address, l.city, l.country,
                o.name AS org_name, o.type AS org_type, o.description AS org_description,
                o.offers_categories, o.needs_categories
         FROM locations l
         JOIN organizations o ON o.id = l.organization_id
         WHERE l.slug = ? AND l.active = 1',
        [$slug]
    ) ?: null;
}

function listingShareIntentCategory(array $row, ?string $intent, ?string $category): array
{
    $offers = json_decode($row['offers_categories'] ?? '[]', true) ?: [];
    $needs = json_decode($row['needs_categories'] ?? '[]', true) ?: [];

    if ($intent === 'giving' && $category && in_array($category, $offers, true)) {
        return ['giving', $category];
    }
    if ($intent === 'needing' && $category && in_array($category, $needs, true)) {
        return ['needing', $category];
    }
    if ($offers) {
        return ['giving', $offers[0]];
    }
    if ($needs) {
        return ['needing', $needs[0]];
    }

    return ['giving', 'food'];
}

function buildListingShareUrl(string $slug, string $intent, string $category): string
{
    return siteUrl() . '/' . rawurlencode($slug) . '/' . $intent . '/' . $category;
}

function listingShareDescription(array $row, string $intent, string $category, callable $t): string
{
    $name = trim($row['location_name'] ?? '') ?: trim($row['org_name'] ?? '');
    $catLabel = $t('filter_' . $category);

    if ($intent === 'needing') {
        return sprintf($t('share_desc_needing'), $name, $catLabel);
    }

    return sprintf($t('share_desc_giving'), $name, $catLabel);
}
