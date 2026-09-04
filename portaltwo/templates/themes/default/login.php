<div class="login-box">
    <h1><?= htmlspecialchars($config['app']['title']) ?></h1>

    <?php if ($error !== ''): ?>
    <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if ($step === 'email'): ?>
    <p class="login-desc">Enter your email address and we'll send you a 6-digit code to sign in.</p>
    <form method="post">
        <input type="hidden" name="action" value="request_otp">
        <label>Email address
            <input type="email" name="email" placeholder="you@example.org" autofocus required>
        </label>
        <button type="submit">Send login code</button>
    </form>
    <?php else: ?>
    <?php if ($info !== ''): ?>
    <p class="login-info"><?= htmlspecialchars($info) ?></p>
    <?php endif; ?>
    <form method="post">
        <input type="hidden" name="action" value="verify_otp">
        <label>6-digit code
            <input type="text" name="code" inputmode="numeric" maxlength="6" autofocus required>
        </label>
        <button type="submit">Verify &amp; sign in</button>
    </form>
    <form method="post">
        <input type="hidden" name="action" value="start_over">
        <button type="submit" class="secondary-button">Use a different email</button>
    </form>
    <?php endif; ?>
</div>
