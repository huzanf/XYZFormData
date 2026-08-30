<?php

declare(strict_types=1);

require_once __DIR__ . '/src/Env.php';
require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/SchemaDetector.php';
require_once __DIR__ . '/src/FormRepository.php';
require_once __DIR__ . '/src/ConfigStore.php';

$config = require __DIR__ . '/config/config.php';

try {
    $portalPdo = Database::connect($config['portal_db'], 'portal');
} catch (Throwable $e) {
    http_response_code(500);
    $title = 'Connection error';
    $content = '<h1>Could not load the portal database</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>';
    include __DIR__ . '/templates/layout.php';
    exit;
}

Auth::requireLogin($portalPdo);

$store = new ConfigStore(__DIR__ . '/data/views.json', __DIR__ . '/config/forms.php');
$formsConfig = $store->forms();

try {
    $pdo = Database::connect($config['db'], 'wp');
    $tables = SchemaDetector::tables($pdo, $config['db']['prefix'], $config['db']['name']);
} catch (Throwable $e) {
    http_response_code(500);
    $title = 'Connection error';
    $content = '<h1>Could not load data</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>';
    include __DIR__ . '/templates/layout.php';
    exit;
}

$formRepo = new FormRepository($pdo, $tables);

foreach ($formsConfig as $formId => &$formDef) {
    $dbForm = $formRepo->getForm((int) $formId);
    $formDef['date_created'] = $dbForm['date_created'] ?? null;
}
unset($formDef);

// Latest-created form first. Forms not found in the database (e.g. a
// wrong ID) sort to the bottom rather than the top.
uasort($formsConfig, fn ($a, $b) => strcmp((string) ($b['date_created'] ?? ''), (string) ($a['date_created'] ?? '')));

$title = $config['app']['title'];

ob_start();
include __DIR__ . '/templates/forms_list.php';
$content = ob_get_clean();

include __DIR__ . '/templates/layout.php';
