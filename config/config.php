<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Env.php';

Env::load(__DIR__ . '/../.env');

return [
    'db' => [
        'host'   => getenv('DB_HOST') ?: '127.0.0.1',
        'port'   => getenv('DB_PORT') ?: '3306',
        'name'   => getenv('DB_NAME') ?: 'wordpress',
        'user'   => getenv('DB_USER') ?: 'wp_readonly',
        'pass'   => getenv('DB_PASS') ?: '',
        'prefix' => getenv('DB_TABLE_PREFIX') ?: 'wp_',
    ],
    'app' => [
        'title'           => getenv('APP_TITLE') ?: 'XYZ Form Data Portal',
        'per_page'        => (int) (getenv('APP_PER_PAGE') ?: 50),
        'max_export_rows' => (int) (getenv('APP_MAX_EXPORT_ROWS') ?: 20000),
    ],
    'auth' => [
        'password_hash' => getenv('PORTAL_PASSWORD_HASH') ?: '',
    ],
];
