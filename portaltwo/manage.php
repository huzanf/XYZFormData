<?php

declare(strict_types=1);

require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/SchemaDetector.php';
require_once __DIR__ . '/src/FormRepository.php';
require_once __DIR__ . '/src/ConfigStore.php';
require_once __DIR__ . '/src/SheetConfigStore.php';

$config = require __DIR__ . '/config/config.php';

/**
 * Pulls an ordered list of field IDs out of a set of numbered "slot"
 * selects (e.g. view_col1, view_col2, ...) — used wherever column/field
 * order matters, which plain checkboxes can't express since they come
 * back in form order, not selection order. Any number of slots is
 * supported (the template decides how many to render); this just collects
 * whichever ones were actually submitted, in slot-number order.
 */
function collectSlots(array $post, string $prefix): array
{
    $indexed = [];
    foreach ($post as $key => $value) {
        if (preg_match('/^' . preg_quote($prefix, '/') . '(\d+)$/', (string) $key, $m) && $value !== '') {
            $indexed[(int) $m[1]] = (int) $value;
        }
    }
    ksort($indexed);

    return array_values($indexed);
}

try {
    $portalPdo = Database::connect($config['portal_db'], 'portal');
} catch (Throwable $e) {
    http_response_code(500);
    $title = 'Connection error';
    $content = '<h1>Could not load the portal database</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>';
    include __DIR__ . '/templates/layout.php';
    exit;
}

Auth::requireAdmin($portalPdo);

$store = new ConfigStore(__DIR__ . '/data/views.json', __DIR__ . '/config/forms.php');
$sheetStore = new SheetConfigStore(__DIR__ . '/data/sheets.json', __DIR__ . '/config/sheets.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $formId = (int) ($_POST['form_id'] ?? 0);

    switch ($action) {
        case 'save_form':
            $label = trim((string) ($_POST['label'] ?? ''));
            if ($formId > 0 && $label !== '') {
                $store->saveForm($formId, $label);
            }
            header('Location: manage.php');
            exit;

        case 'delete_form':
            $store->deleteForm($formId);
            header('Location: manage.php');
            exit;

        case 'save_view':
            $label = trim((string) ($_POST['label'] ?? ''));
            $groupBy = ($_POST['group_by'] ?? '') !== '' ? (int) $_POST['group_by'] : null;
            $columns = collectSlots($_POST, 'view_col');

            if ($formId > 0 && $label !== '') {
                $store->saveView($formId, [
                    'slug'     => (string) ($_POST['slug'] ?? ''),
                    'label'    => $label,
                    'group_by' => $groupBy,
                    'columns'  => empty($columns) ? null : $columns,
                ]);
            }
            header('Location: manage.php?form=' . $formId);
            exit;

        case 'delete_view':
            $store->deleteView($formId, (string) ($_POST['slug'] ?? ''));
            header('Location: manage.php?form=' . $formId);
            exit;

        case 'save_sheet':
            $type = (string) ($_POST['sheet_type'] ?? '');
            $label = trim((string) ($_POST['sheet_label'] ?? ''));
            $validTypes = ['complete', 'group_columns', 'value_sections', 'presence_columns'];

            if ($formId > 0 && $label !== '' && in_array($type, $validTypes, true)) {
                $sheet = [
                    'slug'  => (string) ($_POST['sheet_slug'] ?? ''),
                    'type'  => $type,
                    'label' => $label,
                ];

                switch ($type) {
                    case 'complete':
                        $cols = (isset($_POST['complete_columns']) && is_array($_POST['complete_columns']))
                            ? array_map('intval', $_POST['complete_columns'])
                            : [];
                        $sheet['columns'] = empty($cols) ? null : $cols;
                        break;

                    case 'group_columns':
                        $sheet['group_by'] = (int) ($_POST['group_by'] ?? 0);
                        $sheet['columns'] = collectSlots($_POST, 'gc_col');
                        break;

                    case 'value_sections':
                        $sheet['category_by'] = (int) ($_POST['category_by'] ?? 0);
                        $sheet['columns'] = collectSlots($_POST, 'vs_col');
                        break;

                    case 'presence_columns':
                        $sheet['fields'] = collectSlots($_POST, 'pc_field');
                        $sheet['columns'] = collectSlots($_POST, 'pc_col');
                        break;
                }

                $sheetStore->saveSheet($formId, $sheet);
            }
            header('Location: manage.php?form=' . $formId . '#sheets');
            exit;

        case 'delete_sheet':
            $sheetStore->deleteSheet($formId, (string) ($_POST['slug'] ?? ''));
            header('Location: manage.php?form=' . $formId . '#sheets');
            exit;
    }
}

$formId = isset($_GET['form']) ? (int) $_GET['form'] : null;

ob_start();

if ($formId === null) {
    $forms = $store->forms();
    $title = 'Manage Forms';
    include __DIR__ . '/templates/manage_forms.php';
} else {
    $formEntry = $store->form($formId);

    if ($formEntry === null) {
        http_response_code(404);
        $title = 'Not found';
        echo '<h1>Not found</h1><p>Unknown form. <a href="manage.php">&larr; Back</a></p>';
    } else {
        $allFields = [];
        $fieldsError = null;

        try {
            $pdo = Database::connect($config['db'], 'wp');
            $tables = SchemaDetector::tables($pdo, $config['db']['prefix'], $config['db']['name']);
            $formRepo = new FormRepository($pdo, $tables);
            $allFields = $formRepo->getFields($formId);
        } catch (Throwable $e) {
            $fieldsError = $e->getMessage();
        }

        $views = $formEntry['views'] ?? [];
        $sheets = $sheetStore->sheetsForForm($formId);
        $title = 'Manage Views & Sheets — ' . $formEntry['label'];
        include __DIR__ . '/templates/manage_views.php';
        include __DIR__ . '/templates/manage_sheets.php';
    }
}

$content = ob_get_clean();
include __DIR__ . '/templates/layout.php';
