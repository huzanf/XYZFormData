<div class="auth-card-wrap">
    <div class="auth-mark"><span></span><span></span><span></span></div>
    <div class="auth-app-name">FormPortal</div>
    <div class="auth-app-sub"><?= htmlspecialchars($config['app']['title']) ?></div>

    <div class="auth-card">
        <?php if ($error !== ''): ?>
        <p class="auth-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if ($step === 'email'): ?>
        <h1>Log in</h1>
        <p class="auth-desc">Enter your email address and we'll send you a 6-digit code to securely access your account.</p>
        <form method="post">
            <input type="hidden" name="action" value="request_otp">
            <label>Email address
                <input type="email" name="email" placeholder="you@example.org" autofocus required>
            </label>
            <button type="submit">Send login code</button>
        </form>
        <?php else: ?>
        <h1>Check your email</h1>
        <?php if ($info !== ''): ?>
        <p class="auth-info-box"><span>&#9993;</span><span><?= htmlspecialchars($info) ?></span></p>
        <?php endif; ?>
        <form method="post">
            <input type="hidden" name="action" value="verify_otp">
            <label>Enter 6-digit code
                <input type="text" name="code" inputmode="numeric" maxlength="6" autofocus required class="otp-single">
            </label>
            <button type="submit">Verify &amp; log in</button>
        </form>
        <form method="post" style="margin-top:10px;">
            <input type="hidden" name="action" value="start_over">
            <button type="submit" class="btn-secondary" style="width:100%;">Use a different email</button>
        </form>
        <?php endif; ?>
    </div>

    <div class="auth-security-note">
        <span class="icon-badge">&#128274;</span>
        <span>
            <strong>Your security is our priority</strong>
            We use one-time codes and never share your information with third parties.
        </span>
    </div>
</div>
