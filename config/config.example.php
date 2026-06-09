<?php
/**
 * DogeSeeds.org Configuration
 * Copy this file to config.php and fill in your values.
 * The install wizard creates config.php automatically.
 */

return [
    'db' => [
        'host'     => 'localhost',
        'name'     => 'dogeseeds',
        'user'     => 'your_db_user',
        'password' => 'your_db_password',
        'charset'  => 'utf8mb4',
    ],
    'app' => [
        'name'       => 'DogeSeeds.org',
        'url'        => 'https://yourdomain.com',
        'debug'      => false,
        'timezone'   => 'UTC',
        'secret_key' => 'CHANGE_THIS_TO_A_RANDOM_STRING',
    ],
    'session' => [
        'lifetime' => 7200,
        'name'     => 'dogeseeds_session',
    ],
];
