<?php

declare(strict_types=1);

require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/SchemaDetector.php';
require_once __DIR__ . '/src/FormRepository.php';
require_once __DIR__ . '/src/EntryRepository.php';
require_once __DIR__ . '/src/SheetBuilder.php';
require_once __DIR__ . '/src/ConfigStore.php';
require_once __DIR__ . '/src/SheetConfigStore.php';

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

$formId = isset($_GET['form']) ? (int) $_GET['form'] : 0;
$formDef = $store->form($formId);

if ($formDef === null) {
    http_response_code(404);
    $title = 'Not found';
    $content = '<h1>Not found</h1><p>Unknown form.</p><p><a href="index.php">&larr; Back to forms</a></p>';
    include __DIR__ . '/templates/layout.php';
    exit;
}

$sheetStore = new SheetConfigStore(__DIR__ . '/data/sheets.json', __DIR__ . '/config/sheets.php');
$sheetDefs = $sheetStore->sheetsForForm($formId);

if (empty($sheetDefs)) {
    http_response_code(404);
    $title = 'No sheets configured';
    $content = '<h1>No sheets configured for this form</h1>'
        . '<p><a href="manage.php?form=' . (int) $formId . '#sheets">Add one in Manage Views &amp; Sheets</a>.</p>'
        . '<p><a href="view.php?form=' . (int) $formId . '">&larr; Back to ' . htmlspecialchars($formDef['label']) . '</a></p>';
    include __DIR__ . '/templates/layout.php';
    exit;
}

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
$entryRepo = new EntryRepository($pdo, $tables);
$builder = new SheetBuilder($entryRepo);

$allFields = $formRepo->getFields($formId);

// Sheets reflect the whole current dataset, not a filtered page — same
// row cap as export.php uses, for the same reason (bounded worst case).
$ids = $entryRepo->matchingEntryIds($formId, []);
$ids = array_slice($ids, 0, $config['app']['max_export_rows']);
$entries = $entryRepo->fetchEntriesByIds($ids);
$values = $entryRepo->getValuesForEntries($ids);

$sheets = [];
foreach ($sheetDefs as $sheetDef) {
    $sheets[] = $builder->build($formId, $allFields, $entries, $values, $sheetDef);
}

if (($_GET['export'] ?? '') === 'xlsx') {
    require_once __DIR__ . '/src/XlsxWriter.php';
    $safeLabel = preg_replace('/[^A-Za-z0-9_-]+/', '-', $formDef['label']) ?? 'sheets';
    $filename = trim($safeLabel, '-') . '-sheets-' . date('Y-m-d') . '.xlsx';
    XlsxWriter::streamWorkbook($filename, $sheets);
    exit;
}

$title = $formDef['label'] . ' — Sheets';
$quickViews = $formDef['views'] ?? [];
$qvSlug = null; // tabs.php's quick-view links; none active on this page

ob_start();
include __DIR__ . '/templates/tabs.php';
include __DIR__ . '/templates/sheets_view.php';
$content = ob_get_clean();

include __DIR__ . '/templates/layout.php';
