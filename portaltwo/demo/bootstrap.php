<?php

declare(strict_types=1);

/**
 * Wires up the demo: seeds a local SQLite file (if not already built) and
 * exposes $pdo / $tables / $config the same way the real app's *.php
 * pages expect, but pointed at demo data instead of a live WordPress MySQL
 * database. Forms/views come from ConfigStore, backed by demo/views.json
 * (seeded from demo/forms.php on first run) — same as the real app's
 * data/views.json, just kept separate so the demo never touches a live
 * deployment's config.
 *
 * The real app's login (email + one-time code) needs its own portal
 * database, which the demo deliberately doesn't set up — so this just
 * simulates an already-logged-in admin session directly, letting every
 * page (including Manage Forms/Views/Users) render fully for local
 * preview without any of that.
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
        'title'           => 'XYZ Form Data Portal — Demo',
        'per_page'        => 50,
        'max_export_rows' => 20000,
    ],
];

$viewsStorePath = __DIR__ . '/views.json';
$legacyFormsPath = __DIR__ . '/forms.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Demo Admin';
$_SESSION['user_role'] = 'admin';
