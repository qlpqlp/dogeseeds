<?php

declare(strict_types=1);

$user = Auth::requireAuth();

function myListingRow(int $locationId, int $userId, bool $isAdmin): ?array
{
    $sql = "SELECT l.id AS location_id, l.name AS location_name, l.latitude, l.longitude,
                   l.address, l.city, l.country, l.instructions, l.image_path, l.active,
                   o.id AS org_id, o.user_id, o.name AS org_name, o.type AS org_type,
                   o.description AS org_description, o.offers_categories, o.needs_categories,
                   o.contact_email, o.contact_phone, o.show_contact_public, o.website
            FROM locations l
            JOIN organizations o ON o.id = l.organization_id
            WHERE l.id = ?";
    $params = [$locationId];

    if (!$isAdmin) {
        $sql .= ' AND o.user_id = ?';
        $params[] = $userId;
    }

    return Database::fetch($sql, $params) ?: null;
}

function myListingDonations(int $locationId): array
{
    return Database::fetchAll(
        "SELECT id, title, description, category, quantity, pickup_start, pickup_end, status
         FROM donations WHERE location_id = ? ORDER BY category ASC, id ASC",
        [$locationId]
    );
}

function formatMyListing(array $row): array
{
    $row['offers'] = json_decode($row['offers_categories'] ?? '[]', true) ?: [];
    $row['needs'] = json_decode($row['needs_categories'] ?? '[]', true) ?: [];
    $row['donations'] = myListingDonations((int) $row['location_id']);
    $row['image_url'] = publicPath($row['image_path'] ?? null);
    unset($row['offers_categories'], $row['needs_categories'], $row['image_path']);
    return $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $isAdmin = $user['role'] === 'admin';
    $sql = "SELECT l.id AS location_id, l.name AS location_name, l.latitude, l.longitude,
                   l.address, l.city, l.country, l.instructions, l.image_path, l.active,
                   l.created_at AS location_created_at,
                   o.id AS org_id, o.user_id, o.name AS org_name, o.type AS org_type,
                   o.description AS org_description, o.offers_categories, o.needs_categories,
                   o.contact_email, o.contact_phone, o.show_contact_public, o.website
            FROM locations l
            JOIN organizations o ON o.id = l.organization_id";
    $params = [];

    if (!$isAdmin) {
        $sql .= ' WHERE o.user_id = ?';
        $params[] = $user['id'];
    }

    $sql .= ' ORDER BY l.created_at DESC';
    $rows = Database::fetchAll($sql, $params);
    $listings = array_map('formatMyListing', $rows);

    jsonResponse(['listings' => $listings]);
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $locationId = (int) ($body['location_id'] ?? 0);
    if (!$locationId) {
        jsonResponse(['error' => 'Location ID required'], 400);
    }

    $isAdmin = $user['role'] === 'admin';
    $listing = myListingRow($locationId, (int) $user['id'], $isAdmin);
    if (!$listing) {
        jsonResponse(['error' => 'Not found'], 404);
    }

    $orgUpdates = [];
    if (array_key_exists('description', $body)) {
        $orgUpdates['description'] = trim((string) $body['description']) ?: null;
    }
    if (array_key_exists('contact_email', $body)) {
        $orgUpdates['contact_email'] = trim((string) $body['contact_email']) ?: null;
    }
    if (array_key_exists('contact_phone', $body)) {
        $orgUpdates['contact_phone'] = trim((string) $body['contact_phone']) ?: null;
    }
    if (array_key_exists('show_contact_public', $body)) {
        $orgUpdates['show_contact_public'] = !empty($body['show_contact_public']) ? 1 : 0;
    }

    $locUpdates = [];
    if (array_key_exists('instructions', $body)) {
        $locUpdates['instructions'] = trim((string) $body['instructions']) ?: null;
    }
    if (array_key_exists('address', $body)) {
        $locUpdates['address'] = trim((string) $body['address']) ?: null;
    }
    if (array_key_exists('city', $body)) {
        $locUpdates['city'] = trim((string) $body['city']) ?: null;
    }
    if (array_key_exists('country', $body)) {
        $countries = require ROOT_PATH . '/includes/countries.php';
        $country = $body['country'];
        if ($country && isset($countries[$country])) {
            $locUpdates['country'] = $countries[$country];
        } elseif ($country) {
            $locUpdates['country'] = trim((string) $country) ?: null;
        } else {
            $locUpdates['country'] = null;
        }
    }

    $pickupStart = null;
    $pickupEnd = null;
    if (array_key_exists('pickup_start', $body) || array_key_exists('pickup_end', $body)) {
        $pickupError = validatePickupWindow($body['pickup_start'] ?? null, $body['pickup_end'] ?? null);
        if ($pickupError) {
            jsonResponse(['error' => $pickupError], 400);
        }
        $pickupStart = parsePickupDatetime($body['pickup_start'] ?? null);
        $pickupEnd = parsePickupDatetime($body['pickup_end'] ?? null);
    }

    if ($orgUpdates) {
        Database::update('organizations', $orgUpdates, 'id = ?', [(int) $listing['org_id']]);
    }
    if ($locUpdates) {
        Database::update('locations', $locUpdates, 'id = ?', [$locationId]);
    }

    if ($pickupStart && $pickupEnd) {
        Database::query(
            "UPDATE donations SET pickup_start = ?, pickup_end = ?, updated_at = NOW()
             WHERE location_id = ? AND status IN ('available', 'reserved')",
            [$pickupStart, $pickupEnd, $locationId]
        );
        $shareDescription = $orgUpdates['description'] ?? $listing['org_description'] ?? null;
        if ($shareDescription !== null) {
            Database::query(
                "UPDATE donations SET description = ?, updated_at = NOW()
                 WHERE location_id = ? AND status IN ('available', 'reserved')",
                [$shareDescription, $locationId]
            );
        }
    }

    logActivity((int) $user['id'], 'update_listing', 'location', $locationId);
    $updated = myListingRow($locationId, (int) $user['id'], $isAdmin);
    jsonResponse(['listing' => formatMyListing($updated)]);
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $locationId = (int) ($body['location_id'] ?? $_GET['location_id'] ?? 0);
    if (!$locationId) {
        jsonResponse(['error' => 'Location ID required'], 400);
    }

    $isAdmin = $user['role'] === 'admin';
    $listing = myListingRow($locationId, (int) $user['id'], $isAdmin);
    if (!$listing) {
        jsonResponse(['error' => 'Not found'], 404);
    }

    Database::update('locations', ['active' => 0], 'id = ?', [$locationId]);
    Database::query(
        "UPDATE donations SET status = 'expired', updated_at = NOW() WHERE location_id = ? AND status IN ('available', 'reserved')",
        [$locationId]
    );

    logActivity((int) $user['id'], 'remove_listing', 'location', $locationId);
    jsonResponse(['message' => 'Listing removed from map']);
}

if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
    $donationId = (int) ($body['donation_id'] ?? 0);
    $status = $body['status'] ?? '';

    if (!$donationId || !in_array($status, ['available', 'collected', 'expired'], true)) {
        jsonResponse(['error' => 'Donation ID and valid status required'], 400);
    }

    $isAdmin = $user['role'] === 'admin';
    $donation = Database::fetch(
        "SELECT d.id, d.location_id, o.user_id
         FROM donations d
         JOIN locations l ON l.id = d.location_id
         JOIN organizations o ON o.id = l.organization_id
         WHERE d.id = ?",
        [$donationId]
    );

    if (!$donation || (!$isAdmin && (int) $donation['user_id'] !== (int) $user['id'])) {
        jsonResponse(['error' => 'Not found'], 404);
    }

    Database::update('donations', ['status' => $status], 'id = ?', [$donationId]);
    logActivity((int) $user['id'], 'update_donation_status', 'donation', $donationId, ['status' => $status]);

    $listing = myListingRow((int) $donation['location_id'], (int) $user['id'], $isAdmin);
    jsonResponse(['listing' => $listing ? formatMyListing($listing) : null]);
}

jsonResponse(['error' => 'Method not allowed'], 405);
