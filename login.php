<?php

declare(strict_types=1);

require_once __DIR__ . '/src/Env.php';
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/src/Mailer.php';

$config = require __DIR__ . '/config/config.php';

try {
    $portalPdo = Database::connect($config['portal_db'], 'portal');
} catch (Throwable $e) {
    http_response_code(500);
    $title = 'Connection error';
    $content = '<h1>Could not load the portal database</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>';
    include __DIR__ . '/templates/layout.php';
    exit;
}

if (Auth::check($portalPdo)) {
    header('Location: index.php');
    exit;
}

$auth = new Auth($portalPdo);
$error = '';
$info = '';
$step = ($_SESSION['pending_otp_email'] ?? '') !== '' ? 'code' : 'email';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'request_otp') {
        $email = trim((string) ($_POST['email'] ?? ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Enter a valid email address.';
            $step = 'email';
        } elseif (!$auth->emailExists($email)) {
            $error = 'No portal account found for that email address.';
            $step = 'email';
        } else {
            $auth->requestOtp($email, $config['mail']);
            $_SESSION['pending_otp_email'] = $email;
            $info = "We've emailed a 6-digit code to {$email}. It expires in 10 minutes.";
            $step = 'code';
        }
    } elseif ($action === 'verify_otp') {
        $email = (string) ($_SESSION['pending_otp_email'] ?? '');
        $code = trim((string) ($_POST['code'] ?? ''));

        if ($email !== '' && $auth->verifyOtp($email, $code)) {
            unset($_SESSION['pending_otp_email']);
            $redirect = Auth::safeRedirectTarget((string) ($_GET['redirect'] ?? 'index.php'));
            header('Location: ' . $redirect);
            exit;
        }

        $error = 'That code is incorrect or has expired.';
        $step = 'code';
    } elseif ($action === 'start_over') {
        unset($_SESSION['pending_otp_email']);
        header('Location: login.php');
        exit;
    }
}

$title = 'Sign in';

ob_start();
include __DIR__ . '/templates/login.php';
$content = ob_get_clean();

include __DIR__ . '/templates/layout.php';
