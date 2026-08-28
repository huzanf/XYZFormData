<?php

declare(strict_types=1);

/**
 * Wires up the demo: seeds a local SQLite file (if not already built) and
 * exposes $pdo / $tables / $config / $formsConfig the same way the real
 * app's public/index.php and public/view.php expect, but pointed at demo
 * data instead of a live WordPress MySQL database.
 */

$dbFile = __DIR__ . '/demo.sqlite';

if (!is_file($dbFile)) {
    require __DIR__ . '/seed.php';
}

$pdo = new PDO('sqlite:' . $dbFile, null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$tables = [
    'form'       => 'gf_form',
    'form_meta'  => 'gf_form_meta',
    'entry'      => 'gf_entry',
    'entry_meta' => 'gf_entry_meta',
];

$config = [
    'app' => [
        'title'    => 'XYZ Form Data Dashboard — Demo',
        'per_page' => 50,
    ],
];

$formsConfig = require __DIR__ . '/forms.php';
