<?php
$theme = $config['app']['theme'] ?? 'default';
require __DIR__ . '/themes/' . $theme . '/manage_forms.php';
