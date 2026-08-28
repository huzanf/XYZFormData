<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/FormRepository.php';
require_once __DIR__ . '/../../src/EntryRepository.php';
require __DIR__ . '/../bootstrap.php';

$formId = isset($_GET['form']) ? (int) $_GET['form'] : 0;
$viewSlug = (string) ($_GET['view'] ?? '');
$groupValue = isset($_GET['value']) ? (string) $_GET['value'] : null;
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = $config['app']['per_page'];

if (!isset($formsConfig[$formId]['views'][$viewSlug])) {
    http_response_code(404);
    $title = 'Not found';
    $content = '<h1>Not found</h1><p>Unknown form or view.</p><p><a href="index.php">&larr; Back to forms</a></p>';
    include __DIR__ . '/../../templates/layout.php';
    exit;
}

$formDef = $formsConfig[$formId];
$viewDef = $formDef['views'][$viewSlug];
$groupByField = $viewDef['group_by'] ?? null;

$formRepo = new FormRepository($pdo, $tables);
$entryRepo = new EntryRepository($pdo, $tables);

$fields = $formRepo->getFields($formId);
$title = $formDef['label'] . ' — ' . $viewDef['label'];

ob_start();

if ($groupByField === null) {
    $total = $entryRepo->countEntries($formId);
    $entries = $entryRepo->getEntries($formId, $perPage, ($page - 1) * $perPage);
    $entryIds = array_column($entries, 'id');
    $values = $entryRepo->getValuesForEntries($entryIds);

    include __DIR__ . '/../../templates/entries_table.php';
} elseif ($groupValue === null) {
    $groupLabel = $formRepo->getFieldLabel($fields, $groupByField);
    $groups = $entryRepo->getGroupCounts($formId, $groupByField);

    include __DIR__ . '/../../templates/group_index.php';
} else {
    $total = $entryRepo->countEntriesForGroupValue($formId, $groupByField, $groupValue);
    $entries = $entryRepo->getEntriesForGroupValue($formId, $groupByField, $groupValue, $perPage, ($page - 1) * $perPage);
    $entryIds = array_column($entries, 'id');
    $values = $entryRepo->getValuesForEntries($entryIds);

    include __DIR__ . '/../../templates/entries_table.php';
}

$content = ob_get_clean();
include __DIR__ . '/../../templates/layout.php';
