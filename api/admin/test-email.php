<?php

declare(strict_types=1);

Auth::requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$to = trim($body['email'] ?? Auth::user()['email'] ?? '');
if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['error' => 'Valid email required'], 400);
}

if (!Mailer::isEnabled()) {
    jsonResponse(['error' => 'SMTP is not configured or enabled'], 400);
}

$html = EmailTemplates::wrap(
    'SMTP test',
    '<p>This is a test email from your DogeSeeds admin panel. SMTP is working correctly.</p>'
);

if (!Mailer::send($to, 'DogeSeeds SMTP test', $html)) {
    jsonResponse([
        'error' => Mailer::getLastError() ?: 'Failed to send test email. Check SMTP settings.',
    ], 500);
}

jsonResponse(['message' => 'Test email sent']);
