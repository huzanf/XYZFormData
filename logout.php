<?php

declare(strict_types=1);

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/Auth.php';

$config = require __DIR__ . '/config/config.php';

try {
    $portalPdo = Database::connect($config['portal_db'], 'portal');
    Auth::logout($portalPdo);
} catch (Throwable $e) {
    Auth::logout(null);
}

header('Location: login.php');
