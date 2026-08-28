<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Env.php';
require_once __DIR__ . '/../src/Auth.php';

$config = require __DIR__ . '/../config/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string) ($_POST['password'] ?? '');

    if (Auth::attempt($config, $password)) {
        $redirect = Auth::safeRedirectTarget((string) ($_GET['redirect'] ?? 'index.php'));
        header('Location: ' . $redirect);
        exit;
    }

    $error = 'Incorrect password.';
}

$title = 'Sign in';

ob_start();
include __DIR__ . '/../templates/login.php';
$content = ob_get_clean();

include __DIR__ . '/../templates/layout.php';
