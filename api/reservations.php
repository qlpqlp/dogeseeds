<?php

declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$user = Auth::requireAuth();
$donationId = (int) ($body['donation_id'] ?? 0);
$notes = trim($body['notes'] ?? '');

if (!$donationId) {
    jsonResponse(['error' => 'Donation ID required'], 400);
}

$donation = Database::fetch(
    "SELECT * FROM donations WHERE id = ? AND status = 'available' AND pickup_end > NOW()",
    [$donationId]
);

if (!$donation) {
    jsonResponse(['error' => 'Donation not available'], 404);
}

$id = Database::insert('reservations', [
    'donation_id' => $donationId,
    'user_id'     => $user['id'],
    'notes'       => $notes ?: null,
    'status'      => 'pending',
]);

Database::update('donations', ['status' => 'reserved'], 'id = ?', [$donationId]);
logActivity((int) $user['id'], 'reserve_donation', 'reservation', $id);

jsonResponse(['id' => $id, 'message' => 'Reservation created'], 201);
