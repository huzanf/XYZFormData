-- Run this once against a NEW, empty MySQL database dedicated to this
-- portal (separate from your WordPress database) — e.g. via phpMyAdmin's
-- Import tab, or cPanel's phpMyAdmin SQL tab. This creates the tables
-- this app's login system needs: users, one-time login codes, and an
-- audit trail of login activity.

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL,
    role ENUM('admin', 'viewer') NOT NULL DEFAULT 'viewer',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    current_session_token VARCHAR(64) NULL,
    last_login_at DATETIME NULL,
    last_login_ip VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- One-time login codes emailed to a user. verifyOtp() always checks the
-- MOST RECENT unconsumed row for that user, so an older unused code
-- simply stops being the one that's checked — nothing needs to explicitly
-- invalidate it.
CREATE TABLE IF NOT EXISTS login_otps (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    otp_hash VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    consumed_at DATETIME NULL,
    requested_ip VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_login_otps_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS audit_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    event VARCHAR(50) NOT NULL,
    detail VARCHAR(255) NULL,
    ip_address VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_audit_log_user (user_id),
    KEY idx_audit_log_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- After running this, add your first admin user directly (the Manage
-- Users screen needs an existing admin to sign in and use it):
--
--   INSERT INTO users (name, email, role) VALUES ('Your Name', 'you@example.org', 'admin');
