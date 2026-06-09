<?php

declare(strict_types=1);

$category = $_GET['category'] ?? null;
$type = $_GET['type'] ?? null;
$lat = isset($_GET['lat']) ? (float) $_GET['lat'] : null;
$lng = isset($_GET['lng']) ? (float) $_GET['lng'] : null;
$radius = isset($_GET['radius']) ? (float) $_GET['radius'] : 50;

$sql = "
    SELECT
        l.id AS location_id,
        l.name AS location_name,
        l.slug,
        l.latitude,
        l.longitude,
        l.address,
        l.city,
        l.country,
        l.instructions,
        l.image_path,
        o.id AS org_id,
        o.name AS org_name,
        o.type AS org_type,
        o.verified AS org_verified,
        o.offers_categories,
        o.needs_categories,
        o.description AS org_description,
        o.website AS org_website,
        o.contact_email,
        o.contact_phone,
        o.show_contact_public,
        COUNT(d.id) AS donation_count
    FROM locations l
    JOIN organizations o ON o.id = l.organization_id
    LEFT JOIN donations d ON d.location_id = l.id
        AND d.status = 'available'
        AND d.pickup_end > NOW()
";

$params = [];
$conditions = ['l.active = 1'];

// Category filtering done in PHP to include offers/needs JSON

if ($type) {
    $conditions[] = 'o.type = ?';
    $params[] = $type;
}

$sql .= ' WHERE ' . implode(' AND ', $conditions);
$sql .= ' GROUP BY l.id, l.name, l.slug, l.latitude, l.longitude, l.address, l.city, l.country, l.instructions, l.image_path,
    o.id, o.name, o.type, o.verified, o.offers_categories, o.needs_categories, o.description, o.website,
    o.contact_email, o.contact_phone, o.show_contact_public';

$locations = Database::fetchAll($sql, $params);

if ($category) {
    $locations = array_filter($locations, function ($loc) use ($category) {
        $offers = json_decode($loc['offers_categories'] ?? '[]', true) ?: [];
        $needs = json_decode($loc['needs_categories'] ?? '[]', true) ?: [];
        $hasDonation = (int) $loc['donation_count'] > 0;
        return $hasDonation || in_array($category, $offers, true) || in_array($category, $needs, true);
    });
}

if ($lat !== null && $lng !== null) {
    $locations = array_filter($locations, function ($loc) use ($lat, $lng, $radius) {
        $dist = haversine($lat, $lng, (float) $loc['latitude'], (float) $loc['longitude']);
        $loc['distance_km'] = round($dist, 1);
        return $dist <= $radius;
    });
    usort($locations, fn($a, $b) => ($a['distance_km'] ?? 0) <=> ($b['distance_km'] ?? 0));
}

foreach ($locations as &$loc) {
    $loc['donations'] = Database::fetchAll(
        "SELECT id, title, description, category, quantity, pickup_start, pickup_end, status
         FROM donations
         WHERE location_id = ? AND status = 'available' AND pickup_end > NOW()
         ORDER BY pickup_start ASC",
        [$loc['location_id']]
    );
    $loc['icon'] = orgTypeIcon($loc['org_type']);
    $loc['marker_color'] = orgTypeColor($loc['org_type']);
    $loc['offers'] = json_decode($loc['offers_categories'] ?? '[]', true) ?: [];
    $loc['needs'] = json_decode($loc['needs_categories'] ?? '[]', true) ?: [];
    $loc['image_url'] = publicPath($loc['image_path'] ?? null);

    if (!$loc['show_contact_public']) {
        $loc['contact_email'] = null;
        $loc['contact_phone'] = null;
    }
    unset($loc['offers_categories'], $loc['needs_categories'], $loc['show_contact_public'], $loc['image_path']);
}

jsonResponse(['locations' => array_values($locations)]);

function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
{
    $r = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
    return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
}
