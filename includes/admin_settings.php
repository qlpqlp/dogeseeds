<?php

declare(strict_types=1);

/**
 * Editable site settings (admin panel).
 * Keys not listed here cannot be changed via admin API.
 */
return [
    'general' => [
        'site_name'         => ['type' => 'text',     'default' => 'DogeSeeds.org'],
        'site_url'          => ['type' => 'url',      'default' => ''],
        'default_language'  => ['type' => 'language', 'default' => 'en'],
    ],
    'map' => [
        'map_default_lat'  => ['type' => 'float', 'default' => '38.7223', 'min' => -90,  'max' => 90],
        'map_default_lng'  => ['type' => 'float', 'default' => '-9.1393', 'min' => -180, 'max' => 180],
        'map_default_zoom' => ['type' => 'int',   'default' => '6',       'min' => 1,    'max' => 18],
    ],
    'donations' => [
        'doge_wallet'             => ['type' => 'text',     'default' => ''],
        'doge_transparency_note'  => ['type' => 'textarea', 'default' => ''],
    ],
    'email' => [
        'smtp_enabled'     => ['type' => 'bool',   'default' => '0'],
        'smtp_host'        => ['type' => 'text',   'default' => ''],
        'smtp_port'        => ['type' => 'int',    'default' => '587', 'min' => 1, 'max' => 65535],
        'smtp_encryption'  => ['type' => 'select', 'default' => 'tls', 'options' => ['tls', 'ssl', 'none']],
        'smtp_username'    => ['type' => 'text',   'default' => ''],
        'smtp_password'    => ['type' => 'secret', 'default' => ''],
        'smtp_from_email'  => ['type' => 'email',  'default' => ''],
        'smtp_from_name'   => ['type' => 'text',   'default' => 'DogeSeeds.org'],
    ],
];
