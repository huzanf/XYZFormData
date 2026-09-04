<?php

declare(strict_types=1);

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/Auth.php';

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

Auth::requireAdmin($portalPdo);

$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'save') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $role = ($_POST['role'] ?? '') === 'admin' ? 'admin' : 'viewer';

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $flash = ['type' => 'error', 'message' => 'Enter a name and a valid email address.'];
        } else {
            $stmt = $portalPdo->prepare(
                'INSERT INTO users (name, email, role, is_active) VALUES (?, ?, ?, 1)
                 ON DUPLICATE KEY UPDATE name = VALUES(name), role = VALUES(role), is_active = 1'
            );
            $stmt->execute([$name, strtolower($email), $role]);
            $flash = ['type' => 'ok', 'message' => "User \"{$name}\" ({$role}) saved. They can sign in with {$email} now."];
        }
    } elseif ($action === 'toggle_active' && !empty($_POST['user_id'])) {
        $userId = (int) $_POST['user_id'];

        if ($userId === (int) ($_SESSION['user_id'] ?? 0)) {
            $flash = ['type' => 'error', 'message' => "You can't deactivate your own account."];
        } else {
            $portalPdo->prepare('UPDATE users SET is_active = 1 - is_active WHERE id = ?')->execute([$userId]);
            $flash = ['type' => 'ok', 'message' => 'Updated.'];
        }
    }
}

$users = $portalPdo->query(
    'SELECT id, name, email, role, is_active, last_login_at FROM users ORDER BY name'
)->fetchAll();

$title = 'Manage Users';

ob_start();
include __DIR__ . '/templates/manage_users.php';
$content = ob_get_clean();

include __DIR__ . '/templates/layout.php';
