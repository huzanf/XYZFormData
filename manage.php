<?php

declare(strict_types=1);

require_once __DIR__ . '/src/Env.php';
require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/SchemaDetector.php';
require_once __DIR__ . '/src/FormRepository.php';
require_once __DIR__ . '/src/ConfigStore.php';

$config = require __DIR__ . '/config/config.php';

$portalPdo = Database::connect($config['portal_db'], 'portal');
Auth::requireAdmin($portalPdo);

$store = new ConfigStore(__DIR__ . '/data/views.json', __DIR__ . '/config/forms.php');

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
            $columns = (isset($_POST['columns']) && is_array($_POST['columns']))
                ? array_map('intval', $_POST['columns'])
                : null;

            if ($formId > 0 && $label !== '') {
                $store->saveView($formId, [
                    'slug'     => (string) ($_POST['slug'] ?? ''),
                    'label'    => $label,
                    'group_by' => $groupBy,
                    'columns'  => $columns,
                ]);
            }
            header('Location: manage.php?form=' . $formId);
            exit;

        case 'delete_view':
            $store->deleteView($formId, (string) ($_POST['slug'] ?? ''));
            header('Location: manage.php?form=' . $formId);
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
        $title = 'Manage Views — ' . $formEntry['label'];
        include __DIR__ . '/templates/manage_views.php';
    }
}

$content = ob_get_clean();
include __DIR__ . '/templates/layout.php';
