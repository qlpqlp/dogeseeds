<?php

declare(strict_types=1);

$admin = Auth::requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $q = trim($_GET['q'] ?? '');
    $sql = "SELECT u.id, u.email, u.name, u.role, u.language, u.verified, u.blocked, u.created_at,
                   (SELECT COUNT(*) FROM organizations o WHERE o.user_id = u.id) AS org_count,
                   (SELECT COUNT(*) FROM locations l
                    JOIN organizations o ON o.id = l.organization_id
                    WHERE o.user_id = u.id) AS listing_count
            FROM users u";
    $params = [];

    if ($q !== '') {
        $sql .= ' WHERE u.name LIKE ? OR u.email LIKE ?';
        $like = '%' . $q . '%';
        $params = [$like, $like];
    }

    $sql .= ' ORDER BY u.created_at DESC LIMIT 500';
    $users = Database::fetchAll($sql, $params);

    jsonResponse(['users' => $users]);
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $userId = (int) ($body['id'] ?? 0);
    if (!$userId) {
        jsonResponse(['error' => 'User ID required'], 400);
    }

    $target = Database::fetch('SELECT id, email, role FROM users WHERE id = ?', [$userId]);
    if (!$target) {
        jsonResponse(['error' => 'Not found'], 404);
    }

    $updates = [];

    if (array_key_exists('name', $body)) {
        $name = trim((string) $body['name']);
        if ($name === '') {
            jsonResponse(['error' => 'Name required'], 400);
        }
        $updates['name'] = $name;
    }

    if (array_key_exists('email', $body)) {
        $email = trim((string) $body['email']);
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(['error' => 'Valid email required'], 400);
        }
        $existing = Database::fetch('SELECT id FROM users WHERE email = ? AND id != ?', [$email, $userId]);
        if ($existing) {
            jsonResponse(['error' => 'Email already in use'], 409);
        }
        $updates['email'] = $email;
    }

    if (array_key_exists('role', $body)) {
        $role = (string) $body['role'];
        $allowed = ['user', 'business', 'volunteer', 'ngo', 'admin'];
        if (!in_array($role, $allowed, true)) {
            jsonResponse(['error' => 'Invalid role'], 400);
        }
        if ($userId === (int) $admin['id'] && $role !== 'admin') {
            jsonResponse(['error' => 'Cannot change your own admin role'], 400);
        }
        $updates['role'] = $role;
    }

    if (array_key_exists('verified', $body)) {
        $updates['verified'] = !empty($body['verified']) ? 1 : 0;
    }

    if (array_key_exists('blocked', $body)) {
        if ($userId === (int) $admin['id']) {
            jsonResponse(['error' => 'Cannot block yourself'], 400);
        }
        $updates['blocked'] = !empty($body['blocked']) ? 1 : 0;
    }

    if (!$updates) {
        jsonResponse(['error' => 'No fields to update'], 400);
    }

    Database::update('users', $updates, 'id = ?', [$userId]);
    logActivity((int) $admin['id'], 'admin_update_user', 'user', $userId, $updates);

    $updated = Database::fetch(
        "SELECT u.id, u.email, u.name, u.role, u.language, u.verified, u.blocked, u.created_at,
                (SELECT COUNT(*) FROM organizations o WHERE o.user_id = u.id) AS org_count,
                (SELECT COUNT(*) FROM locations l
                 JOIN organizations o ON o.id = l.organization_id
                 WHERE o.user_id = u.id) AS listing_count
         FROM users u WHERE u.id = ?",
        [$userId]
    );

    jsonResponse(['user' => $updated]);
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $userId = (int) ($body['id'] ?? $_GET['id'] ?? 0);
    if (!$userId) {
        jsonResponse(['error' => 'User ID required'], 400);
    }

    if ($userId === (int) $admin['id']) {
        jsonResponse(['error' => 'Cannot delete yourself'], 400);
    }

    $target = Database::fetch('SELECT id, email FROM users WHERE id = ?', [$userId]);
    if (!$target) {
        jsonResponse(['error' => 'Not found'], 404);
    }

    Database::query('DELETE FROM users WHERE id = ?', [$userId]);
    logActivity((int) $admin['id'], 'admin_delete_user', 'user', $userId, ['email' => $target['email']]);

    jsonResponse(['message' => 'User deleted']);
}

jsonResponse(['error' => 'Method not allowed'], 405);
