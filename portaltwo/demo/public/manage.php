<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/FormRepository.php';
require_once __DIR__ . '/../../src/ConfigStore.php';
require_once __DIR__ . '/../../src/SheetConfigStore.php';
require __DIR__ . '/../bootstrap.php';

/**
 * Pulls an ordered list of field IDs out of a set of numbered "slot"
 * selects (e.g. view_col1, view_col2, ...) — see manage.php (root) for
 * the full explanation. Kept identical here so the demo behaves the same
 * as the real app.
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

$store = new ConfigStore($viewsStorePath, $legacyFormsPath);
$sheetStore = new SheetConfigStore($sheetsStorePath, $legacySheetsPath);

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

        case 'save_payment':
            if (($_POST['enabled'] ?? '') === '1') {
                $statuses = array_values(array_filter(array_map(
                    'trim',
                    explode(',', (string) ($_POST['success_statuses'] ?? 'Paid'))
                )));
                $store->savePaymentConfig($formId, [
                    'enabled'          => true,
                    'mode_field'       => (int) ($_POST['mode_field'] ?? 0),
                    'offline_value'    => trim((string) ($_POST['offline_value'] ?? '')),
                    'success_statuses' => empty($statuses) ? ['Paid'] : $statuses,
                ]);
            } else {
                $store->savePaymentConfig($formId, null);
            }
            header('Location: manage.php?form=' . $formId . '#payment');
            exit;
    }
}

$formId = isset($_GET['form']) ? (int) $_GET['form'] : null;

ob_start();

if ($formId === null) {
    $forms = $store->forms();
    $title = 'Manage Forms';
    include __DIR__ . '/../../templates/manage_forms.php';
} else {
    $formEntry = $store->form($formId);

    if ($formEntry === null) {
        http_response_code(404);
        $title = 'Not found';
        echo '<h1>Not found</h1><p>Unknown form. <a href="manage.php">&larr; Back</a></p>';
    } else {
        $formRepo = new FormRepository($pdo, $tables);
        $allFields = $formRepo->getFields($formId);
        $fieldsError = null;

        $views = $formEntry['views'] ?? [];
        $sheets = $sheetStore->sheetsForForm($formId);
        $paymentConfig = $store->paymentConfig($formId);
        $title = 'Manage Views & Sheets — ' . $formEntry['label'];
        include __DIR__ . '/../../templates/manage_views.php';
        include __DIR__ . '/../../templates/manage_sheets.php';
        include __DIR__ . '/../../templates/manage_payment.php';
    }
}

$content = ob_get_clean();
include __DIR__ . '/../../templates/layout.php';
