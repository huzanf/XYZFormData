<?php

declare(strict_types=1);

require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/EntryOverrideStore.php';

$config = require __DIR__ . '/config/config.php';

try {
    $portalPdo = Database::connect($config['portal_db'], 'portal');
} catch (Throwable $e) {
    http_response_code(500);
    exit('Could not load the portal database: ' . $e->getMessage());
}

Auth::requireAdmin($portalPdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

$store = new EntryOverrideStore($portalPdo);
$formId = (int) ($_POST['form_id'] ?? 0);
$entryId = (int) ($_POST['entry_id'] ?? 0);
$action = (string) ($_POST['action'] ?? '');
$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($formId > 0 && $entryId > 0) {
    switch ($action) {
        case 'hide':
            $store->setHidden($formId, $entryId, true, $userId);
            break;
        case 'unhide':
            $store->setHidden($formId, $entryId, false, $userId);
            break;
        case 'mark_paid':
            $store->setPaid($formId, $entryId, true, $userId);
            break;
        case 'mark_unpaid':
            $store->setPaid($formId, $entryId, false, $userId);
            break;
    }
}

$redirect = Auth::safeRedirectTarget((string) ($_POST['redirect'] ?? ('view.php?form=' . $formId)));
header('Location: ' . $redirect);
exit;
