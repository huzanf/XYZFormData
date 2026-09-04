<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/FormRepository.php';
require_once __DIR__ . '/../../src/EntryRepository.php';
require_once __DIR__ . '/../../src/SheetBuilder.php';
require_once __DIR__ . '/../../src/ConfigStore.php';
require_once __DIR__ . '/../../src/SheetConfigStore.php';
require __DIR__ . '/../bootstrap.php';

$store = new ConfigStore($viewsStorePath, $legacyFormsPath);
$sheetStore = new SheetConfigStore($sheetsStorePath, $legacySheetsPath);
$hasSheets = true; // set below, once we know the form; tabs.php just needs this to exist

$formId = isset($_GET['form']) ? (int) $_GET['form'] : 0;
$formDef = $store->form($formId);

if ($formDef === null) {
    http_response_code(404);
    $title = 'Not found';
    $content = '<h1>Not found</h1><p>Unknown form.</p><p><a href="index.php">&larr; Back to forms</a></p>';
    include __DIR__ . '/../../templates/layout.php';
    exit;
}

$sheetDefs = $sheetStore->sheetsForForm($formId);
$hasSheets = !empty($sheetDefs);

if (empty($sheetDefs)) {
    http_response_code(404);
    $title = 'No sheets configured';
    $content = '<h1>No sheets configured for this form</h1>'
        . '<p><a href="manage.php?form=' . (int) $formId . '#sheets">Add one in Manage Views &amp; Sheets</a>.</p>'
        . '<p><a href="view.php?form=' . (int) $formId . '">&larr; Back to ' . htmlspecialchars($formDef['label']) . '</a></p>';
    include __DIR__ . '/../../templates/layout.php';
    exit;
}

$formRepo = new FormRepository($pdo, $tables);
$entryRepo = new EntryRepository($pdo, $tables);
$builder = new SheetBuilder($entryRepo);

$allFields = $formRepo->getFields($formId);

$paymentConfig = $store->paymentConfig($formId);
$ids = $entryRepo->matchingEntryIds($formId, [], $paymentConfig, []);
$entries = $entryRepo->fetchEntriesByIds($ids);
$values = $entryRepo->getValuesForEntries($ids);

$sheets = [];
foreach ($sheetDefs as $sheetDef) {
    foreach ($builder->build($formId, $allFields, $entries, $values, $sheetDef) as $builtSheet) {
        $sheets[] = $builtSheet;
    }
}

if (($_GET['export'] ?? '') === 'xlsx') {
    require_once __DIR__ . '/../../src/XlsxWriter.php';
    $safeLabel = preg_replace('/[^A-Za-z0-9_-]+/', '-', $formDef['label']) ?? 'sheets';
    $filename = trim($safeLabel, '-') . '-sheets-' . date('Y-m-d') . '.xlsx';
    XlsxWriter::streamWorkbook($filename, $sheets);
    exit;
}

$title = $formDef['label'] . ' — Sheets';
$quickViews = $formDef['views'] ?? [];
$qvSlug = null;

ob_start();
include __DIR__ . '/../../templates/tabs.php';
include __DIR__ . '/../../templates/sheets_view.php';
$content = ob_get_clean();

include __DIR__ . '/../../templates/layout.php';
