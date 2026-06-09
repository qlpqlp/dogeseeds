<?php

declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : null;

    if ($id) {
        $donation = Database::fetch(
            "SELECT d.*, l.name AS location_name, l.latitude, l.longitude, l.address,
                    o.name AS org_name, o.type AS org_type, o.verified AS org_verified
             FROM donations d
             JOIN locations l ON l.id = d.location_id
             JOIN organizations o ON o.id = l.organization_id
             WHERE d.id = ?",
            [$id]
        );
        if (!$donation) {
            jsonResponse(['error' => 'Not found'], 404);
        }
        jsonResponse(['donation' => $donation]);
    }

    $donations = Database::fetchAll(
        "SELECT d.*, l.name AS location_name, l.latitude, l.longitude,
                o.name AS org_name, o.type AS org_type
         FROM donations d
         JOIN locations l ON l.id = d.location_id
         JOIN organizations o ON o.id = l.organization_id
         WHERE d.status = 'available' AND d.pickup_end > NOW()
         ORDER BY d.pickup_start ASC
         LIMIT 100"
    );
    jsonResponse(['donations' => $donations]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = Auth::requireRole('business', 'ngo', 'volunteer', 'admin');

    $locationId = (int) ($body['location_id'] ?? 0);
    $title = trim($body['title'] ?? '');
    $category = $body['category'] ?? 'food';
    $pickupStart = $body['pickup_start'] ?? '';
    $pickupEnd = $body['pickup_end'] ?? '';

    if (!$locationId || !$title || !$pickupStart || !$pickupEnd) {
        jsonResponse(['error' => 'Missing required fields'], 400);
    }

    $location = Database::fetch(
        'SELECT l.*, o.user_id FROM locations l JOIN organizations o ON o.id = l.organization_id WHERE l.id = ?',
        [$locationId]
    );

    if (!$location || ($location['user_id'] != $user['id'] && $user['role'] !== 'admin')) {
        jsonResponse(['error' => 'Forbidden'], 403);
    }

    $allowedCategories = ['food', 'clothing', 'toys', 'essentials', 'other'];
    if (!in_array($category, $allowedCategories, true)) {
        $category = 'food';
    }

    $id = Database::insert('donations', [
        'location_id'  => $locationId,
        'title'        => $title,
        'description'  => $body['description'] ?? null,
        'category'     => $category,
        'quantity'     => $body['quantity'] ?? null,
        'pickup_start' => $pickupStart,
        'pickup_end'   => $pickupEnd,
        'is_recurring' => !empty($body['is_recurring']) ? 1 : 0,
    ]);

    logActivity((int) $user['id'], 'create_donation', 'donation', $id);
    jsonResponse(['id' => $id, 'message' => 'Donation created'], 201);
}
