<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/FormRepository.php';
require_once __DIR__ . '/../../src/EntryRepository.php';
require_once __DIR__ . '/../../src/FilterRequest.php';
require_once __DIR__ . '/../../src/ColumnSelection.php';
require_once __DIR__ . '/../../src/ConfigStore.php';
require __DIR__ . '/../bootstrap.php';

Auth::requireLogin($config);

$store = new ConfigStore($viewsStorePath, $legacyFormsPath);

$formId = isset($_GET['form']) ? (int) $_GET['form'] : 0;
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = $config['app']['per_page'];

$formDef = $store->form($formId);

if ($formDef === null) {
    http_response_code(404);
    $title = 'Not found';
    $content = '<h1>Not found</h1><p>Unknown form.</p><p><a href="index.php">&larr; Back to forms</a></p>';
    include __DIR__ . '/../../templates/layout.php';
    exit;
}

$quickViews = $formDef['views'] ?? [];

$formRepo = new FormRepository($pdo, $tables);
$entryRepo = new EntryRepository($pdo, $tables);

$allFields = $formRepo->getFields($formId);

$qvSlug = isset($_GET['qv']) ? (string) $_GET['qv'] : null;
$activeQuickView = null;
foreach ($quickViews as $qv) {
    if ($qv['slug'] === $qvSlug) {
        $activeQuickView = $qv;
        break;
    }
}
if ($activeQuickView === null) {
    $qvSlug = null;
}

$groupField = null;
if ($activeQuickView !== null && $activeQuickView['group_by'] !== null) {
    foreach ($allFields as $f) {
        if ($f['id'] === $activeQuickView['group_by']) {
            $groupField = $f;
            break;
        }
    }
}

$rawFilterInput = is_array($_GET['f'] ?? null) ? $_GET['f'] : [];

$title = $formDef['label'];

ob_start();
include __DIR__ . '/../../templates/tabs.php';

if ($groupField !== null && !filterHasValue($groupField, $rawFilterInput)) {
    $groups = $entryRepo->getDistinctValues($formId, $groupField['id']);
    include __DIR__ . '/../../templates/quick_view_index.php';
} else {
    $savedColumns = $activeQuickView['columns'] ?? null;
    $queryCols = $_GET['cols'] ?? null;
    $effectiveColumnIds = is_array($queryCols) ? array_map('intval', $queryCols) : $savedColumns;
    $fields = ColumnSelection::filterByIds($allFields, $effectiveColumnIds);

    $filters = FilterRequest::parse($allFields, $_GET);

    $result = $entryRepo->search($formId, $filters, $perPage, ($page - 1) * $perPage);
    $total = $result['total'];
    $entries = $result['entries'];
    $entryIds = array_column($entries, 'id');
    $values = $entryRepo->getValuesForEntries($entryIds);

    $filterableFields = [];
    foreach ($allFields as $field) {
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

    include __DIR__ . '/../../templates/filter_panel.php';
    include __DIR__ . '/../../templates/entries_table.php';
}

$content = ob_get_clean();

include __DIR__ . '/../../templates/layout.php';

function filterHasValue(array $field, array $rawFilterInput): bool
{
    $current = $rawFilterInput[$field['id']] ?? null;

    if ($field['filter_type'] === 'multi') {
        return is_array($current) && count(array_filter($current, fn ($v) => $v !== '')) > 0;
    }

    if ($field['filter_type'] === 'range') {
        return is_array($current) && (($current['min'] ?? '') !== '' || ($current['max'] ?? '') !== '');
    }

    return is_scalar($current) && trim((string) $current) !== '';
}
