<?php
$theme = $config['app']['theme'] ?? 'default';
require __DIR__ . '/themes/' . $theme . '/login.php';
