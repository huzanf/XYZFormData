<div class="login-box">
    <h1><?= htmlspecialchars($config['app']['title']) ?></h1>
    <?php if ($error !== ''): ?>
    <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <form method="post">
        <label>Password
            <input type="password" name="password" autofocus required>
        </label>
        <button type="submit">Sign in</button>
    </form>
</div>
