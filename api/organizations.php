<?php

declare(strict_types=1);

require_once ROOT_PATH . '/includes/upload.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $orgs = Database::fetchAll(
        "SELECT o.*, COUNT(l.id) AS location_count
         FROM organizations o
         LEFT JOIN locations l ON l.organization_id = o.id AND l.active = 1
         GROUP BY o.id
         ORDER BY o.name ASC"
    );
    jsonResponse(['organizations' => $orgs]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = Auth::requireAuth();

    $name = trim($body['name'] ?? '');
    $type = $body['type'] ?? 'other';
    $lat = $body['latitude'] ?? null;
    $lng = $body['longitude'] ?? null;

    if (!$name || $lat === null || $lng === null) {
        jsonResponse(['error' => 'Name and location are required'], 400);
    }

    $allowedTypes = ['person', 'donor', 'farmer', 'fisherman', 'supermarket', 'grocery', 'restaurant', 'cafe', 'ngo', 'scout', 'volunteer', 'other'];
    if (!in_array($type, $allowedTypes, true)) {
        $type = 'other';
    }

    $offersRaw = $body['offers'] ?? null;
    $needsRaw = $body['needs'] ?? null;
    if (is_string($offersRaw)) {
        $offersRaw = [$offersRaw];
    }
    if (is_string($needsRaw)) {
        $needsRaw = [$needsRaw];
    }

    $offers = parseCategories(is_array($offersRaw) ? $offersRaw : null);
    $needs = parseCategories(is_array($needsRaw) ? $needsRaw : null);

    if (!$offers && !$needs) {
        jsonResponse(['error' => 'Select at least one offer or need category'], 400);
    }

    if ($type === 'donor' && !$offers) {
        jsonResponse(['error' => 'Individual sharers must select at least one item to share'], 400);
    }

    $showPublic = !empty($body['show_contact_public']);
    $contactEmail = trim($body['contact_email'] ?? '') ?: null;
    $contactPhone = trim($body['contact_phone'] ?? '') ?: null;

    $imagePath = !empty($_FILES['image']) ? saveLocationImage($_FILES['image']) : null;

    $orgId = Database::insert('organizations', [
        'user_id'             => $user['id'],
        'name'                => $name,
        'type'                => $type,
        'description'         => $body['description'] ?? null,
        'offers_categories'   => $offers ? json_encode($offers) : null,
        'needs_categories'    => $needs ? json_encode($needs) : null,
        'contact_email'       => $contactEmail,
        'contact_phone'       => $contactPhone,
        'show_contact_public' => $showPublic ? 1 : 0,
        'website'             => $body['website'] ?? null,
    ]);

    $country = $body['country'] ?? null;
    $countries = require ROOT_PATH . '/includes/countries.php';
    if ($country && !isset($countries[$country])) {
        $country = 'OTHER';
    }

    $locationName = trim($body['location_name'] ?? '') ?: $name;
    $slug = uniqueLocationSlug(locationSlugBase((int) $user['id'], $locationName ?: $name));

    $locationId = Database::insert('locations', [
        'organization_id' => $orgId,
        'name'            => $locationName,
        'slug'            => $slug,
        'latitude'        => $lat,
        'longitude'       => $lng,
        'address'         => $body['address'] ?? null,
        'city'            => $body['city'] ?? null,
        'country'         => $country ? ($countries[$country] ?? $country) : null,
        'instructions'    => $body['instructions'] ?? null,
        'image_path'      => $imagePath,
    ]);

    $pickupError = validatePickupWindow($body['pickup_start'] ?? null, $body['pickup_end'] ?? null);
    if ($pickupError) {
        jsonResponse(['error' => $pickupError], 400);
    }

    $pickupStart = parsePickupDatetime($body['pickup_start'] ?? null);
    $pickupEnd = parsePickupDatetime($body['pickup_end'] ?? null);
    $shareDescription = trim($body['description'] ?? '') ?: null;

    foreach ($offers as $category) {
        Database::insert('donations', [
            'location_id'   => $locationId,
            'title'         => donationTitleForCategory($category),
            'description'   => $shareDescription,
            'category'      => $category,
            'quantity'      => trim($body['quantity'] ?? '') ?: 'Available for pickup',
            'pickup_start'  => $pickupStart,
            'pickup_end'    => $pickupEnd,
            'status'        => 'available',
        ]);
    }

    if (Mailer::isEnabled() && !empty($user['email'])) {
        Mailer::send(
            $user['email'],
            'Your listing is live on DogeSeeds',
            EmailTemplates::listingPublished(
                $user['name'],
                $name,
                $pickupStart,
                $pickupEnd,
                siteUrl()
            )
        );
    }

    logActivity((int) $user['id'], 'create_organization', 'organization', $orgId);
    jsonResponse([
        'organization_id' => $orgId,
        'location_id'     => $locationId,
        'image_path'      => $imagePath,
    ], 201);
}
