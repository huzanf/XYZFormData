<?php

declare(strict_types=1);

require_once __DIR__ . '/Mailer.php';

/**
 * Multi-user, passwordless login: email + a one-time 6-digit code, backed
 * by this app's own portal database (separate from the read-only WordPress
 * connection). Enforces one active session per user — a fresh login
 * anywhere issues a new session_token that overwrites the one on the
 * user's row, so check() immediately invalidates any older session the
 * next time it's checked.
 *
 * Manage users at users.php (admin only). Roles: 'admin' (full access,
 * including Manage Forms/Views/Users) or 'viewer' (view and export data
 * only).
 */
final class Auth
{
    private const OTP_TTL_MINUTES = 10;
    private const OTP_MAX_ATTEMPTS = 5;
    private const OTP_RESEND_COOLDOWN_SECONDS = 60;
    public const SESSION_IDLE_SECONDS = 1800;

    public function __construct(private PDO $pdo)
    {
    }

    public function emailExists(string $email): bool
    {
        return $this->findActiveUserByEmail(trim(strtolower($email))) !== null;
    }

    /**
     * @return bool true if either an email was just sent successfully, or a
     *              code from a moments-ago request is still valid (cooldown) —
     *              false only if sending was actually attempted and failed, or
     *              the account doesn't exist (that case is intentionally
     *              indistinguishable from success to the caller, so a login
     *              attempt can't be used to discover which emails have accounts).
     */
    public function requestOtp(string $email, array $mailConfig): bool
    {
        $email = trim(strtolower($email));
        $user = $this->findActiveUserByEmail($email);

        if ($user === null) {
            return true;
        }

        $recent = $this->pdo->prepare('SELECT created_at FROM login_otps WHERE user_id = ? ORDER BY id DESC LIMIT 1');
        $recent->execute([$user['id']]);
        $lastRequestedAt = $recent->fetchColumn();
        if ($lastRequestedAt && (time() - strtotime((string) $lastRequestedAt)) < self::OTP_RESEND_COOLDOWN_SECONDS) {
            return true;
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = date('Y-m-d H:i:s', time() + self::OTP_TTL_MINUTES * 60);

        $this->pdo->prepare(
            'INSERT INTO login_otps (user_id, otp_hash, expires_at, requested_ip) VALUES (?, ?, ?, ?)'
        )->execute([$user['id'], password_hash($code, PASSWORD_DEFAULT), $expiresAt, self::clientIp()]);

        $sent = Mailer::send(
            $mailConfig,
            $email,
            'Your ' . ($mailConfig['app_name'] ?? 'Portal') . ' login code',
            "Your login code is: {$code}\n\nIt expires in " . self::OTP_TTL_MINUTES . " minutes. "
                . "If you didn't request this, you can ignore this email.\n"
        );

        $this->recordAudit($sent ? 'otp_requested' : 'otp_send_failed', (int) $user['id'], $email);

        return $sent;
    }

    public function verifyOtp(string $email, string $code): bool
    {
        $email = trim(strtolower($email));
        $user = $this->findActiveUserByEmail($email);
        if ($user === null) {
            return false;
        }

        $stmt = $this->pdo->prepare(
            'SELECT * FROM login_otps WHERE user_id = ? AND consumed_at IS NULL ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([$user['id']]);
        $otp = $stmt->fetch();

        if ($otp === false) {
            $this->recordAudit('otp_failed', (int) $user['id'], $email);
            return false;
        }
        if (strtotime((string) $otp['expires_at']) < time()) {
            $this->recordAudit('otp_expired', (int) $user['id'], $email);
            return false;
        }
        if ((int) $otp['attempts'] >= self::OTP_MAX_ATTEMPTS) {
            $this->recordAudit('otp_failed', (int) $user['id'], $email);
            return false;
        }
        if (!password_verify($code, $otp['otp_hash'])) {
            $this->pdo->prepare('UPDATE login_otps SET attempts = attempts + 1 WHERE id = ?')->execute([$otp['id']]);
            $this->recordAudit('otp_failed', (int) $user['id'], $email);
            return false;
        }

        $this->pdo->prepare('UPDATE login_otps SET consumed_at = NOW() WHERE id = ?')->execute([$otp['id']]);

        session_regenerate_id(true);
        $sessionToken = bin2hex(random_bytes(32));

        $this->pdo->prepare(
            'UPDATE users SET current_session_token = ?, last_login_at = NOW(), last_login_ip = ? WHERE id = ?'
        )->execute([$sessionToken, self::clientIp(), $user['id']]);

        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['session_token'] = $sessionToken;
        $_SESSION['last_activity'] = time();

        $this->recordAudit('login', (int) $user['id'], $email);

        return true;
    }

    public static function requireLogin(PDO $pdo): void
    {
        self::ensureSession();

        if (self::check($pdo)) {
            return;
        }

        $target = $_SERVER['REQUEST_URI'] ?? 'index.php';
        header('Location: login.php?redirect=' . urlencode($target));
        exit;
    }

    public static function requireAdmin(PDO $pdo): void
    {
        self::requireLogin($pdo);

        if (!self::isAdmin()) {
            http_response_code(403);
            exit('You need administrator access to view this page.');
        }
    }

    /**
     * True only if this browser's session is BOTH authenticated and still
     * the most recent login for this user — a newer login elsewhere (or
     * being deactivated) clears this.
     */
    public static function check(PDO $pdo): bool
    {
        self::ensureSession();

        if (empty($_SESSION['user_id']) || empty($_SESSION['session_token'])) {
            return false;
        }

        $idleSeconds = time() - (int) ($_SESSION['last_activity'] ?? time());
        if ($idleSeconds > self::SESSION_IDLE_SECONDS) {
            self::recordAuditStatic($pdo, 'session_timeout', (int) $_SESSION['user_id'], $_SESSION['user_name'] ?? null);
            self::logout(null);
            return false;
        }

        $stmt = $pdo->prepare('SELECT current_session_token FROM users WHERE id = ? AND is_active = 1');
        $stmt->execute([$_SESSION['user_id']]);
        $currentToken = $stmt->fetchColumn();

        if ($currentToken === false || !hash_equals((string) $currentToken, (string) $_SESSION['session_token'])) {
            self::recordAuditStatic($pdo, 'session_replaced', (int) $_SESSION['user_id'], $_SESSION['user_name'] ?? null);
            self::logout(null);
            return false;
        }

        $_SESSION['last_activity'] = time();

        return true;
    }

    public static function isLoggedIn(): bool
    {
        self::ensureSession();

        return !empty($_SESSION['user_id']);
    }

    public static function isAdmin(): bool
    {
        return ($_SESSION['user_role'] ?? null) === 'admin';
    }

    public static function currentUserName(): ?string
    {
        return $_SESSION['user_name'] ?? null;
    }

    public static function logout(?PDO $pdo): void
    {
        self::ensureSession();

        if ($pdo !== null && !empty($_SESSION['user_id'])) {
            self::recordAuditStatic($pdo, 'logout', (int) $_SESSION['user_id'], $_SESSION['user_name'] ?? null);
        }

        $_SESSION = [];
        session_destroy();
    }

    /**
     * Only allow same-app relative redirect targets, to avoid an
     * open-redirect via a crafted ?redirect= value.
     */
    public static function safeRedirectTarget(string $target): string
    {
        if ($target === '' || str_starts_with($target, '//') || preg_match('#^[a-z][a-z0-9+.-]*://#i', $target)) {
            return 'index.php';
        }

        return $target;
    }

    private function findActiveUserByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    private function recordAudit(string $event, ?int $userId, ?string $detail): void
    {
        self::recordAuditStatic($this->pdo, $event, $userId, $detail);
    }

    private static function recordAuditStatic(PDO $pdo, string $event, ?int $userId, ?string $detail): void
    {
        $pdo->prepare(
            'INSERT INTO audit_log (user_id, event, detail, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())'
        )->execute([$userId, $event, $detail, self::clientIp()]);
    }

    private static function clientIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    private static function ensureSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }
}
