<?php
$theme = $config['app']['theme'] ?? 'default';
require __DIR__ . '/themes/' . $theme . '/quick_view_index.php';
