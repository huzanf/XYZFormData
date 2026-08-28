<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Env.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/SchemaDetector.php';
require_once __DIR__ . '/../src/FormRepository.php';
require_once __DIR__ . '/../src/EntryRepository.php';

$config = require __DIR__ . '/../config/config.php';
$formsConfig = require __DIR__ . '/../config/forms.php';

$title = $config['app']['title'];

ob_start();
include __DIR__ . '/../templates/forms_list.php';
$content = ob_get_clean();

include __DIR__ . '/../templates/layout.php';
