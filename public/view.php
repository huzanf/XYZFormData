<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Env.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/SchemaDetector.php';
require_once __DIR__ . '/../src/FormRepository.php';
require_once __DIR__ . '/../src/EntryRepository.php';
require_once __DIR__ . '/../src/FilterRequest.php';

$config = require __DIR__ . '/../config/config.php';
$formsConfig = require __DIR__ . '/../config/forms.php';

Auth::requireLogin($config);

$formId = isset($_GET['form']) ? (int) $_GET['form'] : 0;
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = $config['app']['per_page'];

if (!isset($formsConfig[$formId])) {
    http_response_code(404);
    $title = 'Not found';
    $content = '<h1>Not found</h1><p>Unknown form.</p><p><a href="index.php">&larr; Back to forms</a></p>';
    include __DIR__ . '/../templates/layout.php';
    exit;
}

$formDef = $formsConfig[$formId];

try {
    $pdo = Database::connect($config['db']);
    $tables = SchemaDetector::tables($pdo, $config['db']['prefix'], $config['db']['name']);
} catch (Throwable $e) {
    http_response_code(500);
    $title = 'Connection error';
    $content = '<h1>Could not load data</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>';
    include __DIR__ . '/../templates/layout.php';
    exit;
}

$formRepo = new FormRepository($pdo, $tables);
$entryRepo = new EntryRepository($pdo, $tables);

$fields = $formRepo->getFields($formId);
$filters = FilterRequest::parse($fields, $_GET);

$result = $entryRepo->search($formId, $filters, $perPage, ($page - 1) * $perPage);
$total = $result['total'];
$entries = $result['entries'];
$entryIds = array_column($entries, 'id');
$values = $entryRepo->getValuesForEntries($entryIds);

// Build the filter panel's UI data: for choice/multi fields, options come
// from values actually present in the data (so they always match what's
// filterable); current selections are read back from the query string so
// the panel reflects the filters currently applied.
$filterableFields = [];
$rawFilterInput = is_array($_GET['f'] ?? null) ? $_GET['f'] : [];
foreach ($fields as $field) {
    $ui = $field;
    $current = $rawFilterInput[$field['id']] ?? null;

    switch ($field['filter_type']) {
        case 'choice':
        case 'multi':
            $ui['distinct'] = $entryRepo->getDistinctValues($formId, $field['id']);
            $ui['selected'] = $field['filter_type'] === 'multi'
                ? (is_array($current) ? array_map('strval', $current) : [])
                : (is_scalar($current) ? (string) $current : '');
            break;
        case 'range':
            $ui['selected_min'] = is_array($current) ? (string) ($current['min'] ?? '') : '';
            $ui['selected_max'] = is_array($current) ? (string) ($current['max'] ?? '') : '';
            break;
        default:
            $ui['selected'] = is_scalar($current) ? (string) $current : '';
    }

    $filterableFields[] = $ui;
}

$title = $formDef['label'];

ob_start();
include __DIR__ . '/../templates/filter_panel.php';
include __DIR__ . '/../templates/entries_table.php';
$content = ob_get_clean();

include __DIR__ . '/../templates/layout.php';
