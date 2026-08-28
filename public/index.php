<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Env.php';
require_once __DIR__ . '/../src/Auth.php';

$config = require __DIR__ . '/../config/config.php';
$formsConfig = require __DIR__ . '/../config/forms.php';

Auth::requireLogin($config);

$title = $config['app']['title'];

ob_start();
include __DIR__ . '/../templates/forms_list.php';
$content = ob_get_clean();

include __DIR__ . '/../templates/layout.php';
