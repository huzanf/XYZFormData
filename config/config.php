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
    'portal_db' => [
        'host' => getenv('PORTAL_DB_HOST') ?: '127.0.0.1',
        'port' => getenv('PORTAL_DB_PORT') ?: '3306',
        'name' => getenv('PORTAL_DB_NAME') ?: '',
        'user' => getenv('PORTAL_DB_USER') ?: '',
        'pass' => getenv('PORTAL_DB_PASS') ?: '',
    ],
    'mail' => [
        'from'     => getenv('MAIL_FROM') ?: '',
        'app_name' => getenv('APP_TITLE') ?: 'XYZ Form Data Portal',
    ],
    'app' => [
        'title'           => getenv('APP_TITLE') ?: 'XYZ Form Data Portal',
        'per_page'        => (int) (getenv('APP_PER_PAGE') ?: 50),
        'max_export_rows' => (int) (getenv('APP_MAX_EXPORT_ROWS') ?: 20000),
    ],
];
