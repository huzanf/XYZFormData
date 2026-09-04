<?php
$onSheetsPage = basename((string) ($_SERVER['SCRIPT_NAME'] ?? '')) === 'sheets.php';
// The including page may already know this (e.g. the demo, whose sheet
// definitions live under demo/ rather than the root app's data/sheets.json)
// — only fall back to the default root-relative store if it doesn't.
if (!isset($hasSheets)) {
    require_once dirname(__DIR__) . '/src/SheetConfigStore.php';
    $sheetStoreForTabs = new SheetConfigStore(dirname(__DIR__) . '/data/sheets.json', dirname(__DIR__) . '/config/sheets.php');
    $hasSheets = !empty($sheetStoreForTabs->sheetsForForm($formId));
}

$theme = $config['app']['theme'] ?? 'default';
require __DIR__ . '/themes/' . $theme . '/tabs.php';
