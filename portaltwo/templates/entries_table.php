<?php
$theme = $config['app']['theme'] ?? 'default';
require __DIR__ . '/themes/' . $theme . '/entries_table.php';
