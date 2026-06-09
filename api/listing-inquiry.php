<?php

declare(strict_types=1);

$locationId = (int) ($body['location_id'] ?? 0);
$name = trim($body['name'] ?? '');
$email = trim($body['email'] ?? '');
$message = trim($body['message'] ?? '');

if (!$locationId || $message === '') {
    jsonResponse(['error' => 'Location and message required'], 400);
}

if (strlen($message) < 10) {
    jsonResponse(['error' => 'Message is too short'], 400);
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['error' => 'Invalid email address'], 400);
}

$loc = Database::fetch(
    'SELECT l.id, l.name AS location_name, l.address, l.city, l.country,
            o.name AS org_name, o.contact_email, u.email AS owner_email
     FROM locations l
     JOIN organizations o ON o.id = l.organization_id
     JOIN users u ON u.id = o.user_id
     WHERE l.id = ? AND l.active = 1',
    [$locationId]
);

if (!$loc) {
    jsonResponse(['error' => 'Listing not found'], 404);
}

$recipient = trim($loc['contact_email'] ?? '') ?: trim($loc['owner_email'] ?? '');
if ($recipient === '') {
    jsonResponse(['error' => 'This listing cannot receive messages'], 503);
}

if (!Mailer::isEnabled()) {
    jsonResponse(['error' => 'Email is not configured on this site'], 503);
}

$listingLabel = $loc['location_name'];
if (strcasecmp(trim($loc['location_name']), trim($loc['org_name'])) !== 0) {
    $listingLabel .= ' (' . $loc['org_name'] . ')';
}
$subject = 'Message about your listing: ' . $listingLabel;

$addressParts = array_filter([
    trim($loc['address'] ?? ''),
    trim($loc['city'] ?? ''),
    trim($loc['country'] ?? ''),
]);
$listingAddress = implode(', ', $addressParts);

$senderLabel = $name !== '' ? $name : ($email !== '' ? $email : 'A map visitor');

$ownerSent = Mailer::send(
    $recipient,
    $subject,
    EmailTemplates::listingInquiry(
        $loc['location_name'],
        $loc['org_name'],
        $senderLabel,
        $email,
        $message,
        true,
        $listingAddress
    )
);

if (!$ownerSent) {
    jsonResponse(['error' => Mailer::getLastError() ?: 'Could not send message'], 500);
}

$copySent = false;
if ($email !== '') {
    $copySent = Mailer::send(
        $email,
        'Copy: ' . $subject,
        EmailTemplates::listingInquiry(
            $loc['location_name'],
            $loc['org_name'],
            $senderLabel,
            $email,
            $message,
            false,
            $listingAddress
        )
    );
}

jsonResponse([
    'message' => 'Message sent',
    'copy_sent' => $copySent,
]);
