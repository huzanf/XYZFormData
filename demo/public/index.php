<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/FormRepository.php';
require_once __DIR__ . '/../../src/EntryRepository.php';
require __DIR__ . '/../bootstrap.php';

Auth::requireLogin($config);

$formRepo = new FormRepository($pdo, $tables);

foreach ($formsConfig as $formId => &$formDef) {
    $dbForm = $formRepo->getForm($formId);
    $formDef['date_created'] = $dbForm['date_created'] ?? null;
}
unset($formDef);

uasort($formsConfig, fn ($a, $b) => strcmp((string) ($b['date_created'] ?? ''), (string) ($a['date_created'] ?? '')));

$title = $config['app']['title'];

ob_start();
include __DIR__ . '/../../templates/forms_list.php';
$content = ob_get_clean();

include __DIR__ . '/../../templates/layout.php';
