<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/FormRepository.php';
require_once __DIR__ . '/../../src/EntryRepository.php';
require __DIR__ . '/../bootstrap.php';

Auth::requireLogin($config);

$title = $config['app']['title'];

ob_start();
include __DIR__ . '/../../templates/forms_list.php';
$content = ob_get_clean();

include __DIR__ . '/../../templates/layout.php';
