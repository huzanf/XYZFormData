<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Env.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/SchemaDetector.php';
require_once __DIR__ . '/../src/FormRepository.php';
require_once __DIR__ . '/../src/EntryRepository.php';
require_once __DIR__ . '/../src/FilterRequest.php';
require_once __DIR__ . '/../src/XlsxWriter.php';

$config = require __DIR__ . '/../config/config.php';
$formsConfig = require __DIR__ . '/../config/forms.php';

Auth::requireLogin($config);

$formId = isset($_GET['form']) ? (int) $_GET['form'] : 0;

if (!isset($formsConfig[$formId])) {
    http_response_code(404);
    exit('Unknown form.');
}

$formDef = $formsConfig[$formId];

$pdo = Database::connect($config['db']);
$tables = SchemaDetector::tables($pdo, $config['db']['prefix'], $config['db']['name']);

$formRepo = new FormRepository($pdo, $tables);
$entryRepo = new EntryRepository($pdo, $tables);

$fields = $formRepo->getFields($formId);
$filters = FilterRequest::parse($fields, $_GET);

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
