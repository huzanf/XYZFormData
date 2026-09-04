<?php

declare(strict_types=1);

require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/SchemaDetector.php';
require_once __DIR__ . '/src/FormRepository.php';
require_once __DIR__ . '/src/EntryRepository.php';
require_once __DIR__ . '/src/FilterRequest.php';
require_once __DIR__ . '/src/ColumnSelection.php';
require_once __DIR__ . '/src/XlsxWriter.php';
require_once __DIR__ . '/src/ConfigStore.php';

$config = require __DIR__ . '/config/config.php';

try {
    $portalPdo = Database::connect($config['portal_db'], 'portal');
} catch (Throwable $e) {
    http_response_code(500);
    exit('Could not load the portal database: ' . $e->getMessage());
}

Auth::requireLogin($portalPdo);

$store = new ConfigStore(__DIR__ . '/data/views.json', __DIR__ . '/config/forms.php');

$formId = isset($_GET['form']) ? (int) $_GET['form'] : 0;
$formDef = $store->form($formId);

if ($formDef === null) {
    http_response_code(404);
    exit('Unknown form.');
}

try {
    $pdo = Database::connect($config['db'], 'wp');
    $tables = SchemaDetector::tables($pdo, $config['db']['prefix'], $config['db']['name']);
} catch (Throwable $e) {
    http_response_code(500);
    exit('Could not load data: ' . $e->getMessage());
}

$formRepo = new FormRepository($pdo, $tables);
$entryRepo = new EntryRepository($pdo, $tables);

$allFields = $formRepo->getFields($formId);

// Match view.php's column resolution: an explicit ?cols[]= wins, else
// fall back to the active saved view's column list (if any), else all.
$qvSlug = isset($_GET['qv']) ? (string) $_GET['qv'] : null;
$activeQuickView = null;
foreach ($formDef['views'] ?? [] as $qv) {
    if ($qv['slug'] === $qvSlug) {
        $activeQuickView = $qv;
        break;
    }
}

$savedColumns = $activeQuickView['columns'] ?? null;
$queryCols = $_GET['cols'] ?? null;
$effectiveColumnIds = is_array($queryCols) ? array_map('intval', $queryCols) : $savedColumns;
$fields = ColumnSelection::filterByIds($allFields, $effectiveColumnIds);

$filters = FilterRequest::parse($allFields, $_GET);

$ids = $entryRepo->matchingEntryIds($formId, $filters);
$ids = array_slice($ids, 0, $config['app']['max_export_rows']);

$entries = $entryRepo->fetchEntriesByIds($ids);
$values = $entryRepo->getValuesForEntries($ids);

$headers = ['ID', 'Date'];
foreach ($fields as $field) {
    $headers[] = $field['label'];
}

$rows = (function () use ($entries, $fields, $values) {
    foreach ($entries as $entry) {
        $row = [(string) $entry['id'], (string) $entry['date_created']];
        foreach ($fields as $field) {
            $cell = $values[(int) $entry['id']][$field['id']] ?? [];
            $row[] = implode(', ', $cell);
        }
        yield $row;
    }
})();

$safeLabel = preg_replace('/[^A-Za-z0-9_-]+/', '-', $formDef['label']) ?? 'export';
$filename = trim($safeLabel, '-') . '-' . date('Y-m-d') . '.xlsx';

XlsxWriter::stream($filename, $headers, $rows);
