<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($title ?? $config['app']['title']) ?></title>
<link rel="stylesheet" href="assets/themes/custom1/style.css">
</head>
<body>
<?php $loggedIn = class_exists('Auth') && Auth::isLoggedIn(); ?>
<?php if (!$loggedIn): ?>

<div class="auth-shell">
<main><?= $content ?></main>
</div>

<?php else: ?>

<div class="app-shell">
<aside class="sidebar">
    <a href="index.php" class="sidebar-brand" style="text-decoration:none;">
        <span class="auth-mark"><span></span><span></span><span></span></span>
        <span class="sidebar-brand-text">
            <div class="sidebar-brand-name">FormPortal</div>
            <div class="sidebar-brand-sub"><?= htmlspecialchars($config['app']['title']) ?></div>
        </span>
    </a>

    <?php $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? '')); ?>
    <nav class="sidebar-nav">
        <a href="index.php" class="<?= $script === 'index.php' ? 'active' : '' ?>"><span class="nav-icon">&#9633;</span> Forms</a>
        <?php if (Auth::isAdmin()): ?>
        <a href="manage.php" class="<?= $script === 'manage.php' ? 'active' : '' ?>"><span class="nav-icon">&#9881;</span> Manage Forms</a>
        <a href="users.php" class="<?= $script === 'users.php' ? 'active' : '' ?>"><span class="nav-icon">&#9787;</span> Manage Users</a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-spacer"></div>

    <div class="sidebar-footer">
        <div class="sidebar-user-name"><?= htmlspecialchars(Auth::currentUserName() ?? '') ?></div>
        <div class="sidebar-user-role"><?= Auth::isAdmin() ? 'Admin' : 'Viewer' ?></div>
        <p style="margin:10px 0 0;"><a href="logout.php">Log out</a></p>
    </div>
</aside>

<div class="app-main">
    <header class="app-topbar">
        <div class="app-topbar-title"><?= htmlspecialchars($title ?? $config['app']['title']) ?></div>
    </header>
    <main class="app-content">
    <?= $content ?>
    </main>
</div>
</div>

<?php endif; ?>
</body>
</html>
