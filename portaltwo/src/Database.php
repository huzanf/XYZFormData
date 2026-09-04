<?php

declare(strict_types=1);

// Auth.php compares MySQL-generated timestamps (NOW(), via the 'portal'
// connection) against PHP's own time()/strtotime() for OTP expiry and
// resend-cooldown checks. If the server's PHP and MySQL default timezones
// disagree, those comparisons silently break (e.g. a cooldown check that's
// always true, permanently blocking new OTP emails). Pin PHP's clock to UTC
// here so it's not left to whatever the host happens to default to.
date_default_timezone_set('UTC');

/**
 * Caches one PDO connection per named config (e.g. 'wp' for the read-only
 * WordPress connection, 'portal' for this app's own users/audit-log
 * database) so both can be open at once without interfering.
 */
final class Database
{
    /** @var array<string, PDO> */
    private static array $connections = [];

    public static function connect(array $config, string $name = 'default'): PDO
    {
        if (isset(self::$connections[$name])) {
            return self::$connections[$name];
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $config['host'],
            $config['port'],
            $config['name']
        );

        $pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        if ($name === 'portal') {
            // Match MySQL's NOW() to the UTC clock PHP now uses, so
            // Auth.php's time()/strtotime() comparisons against
            // MySQL-generated timestamps (created_at, NOW()) stay correct
            // regardless of the server's configured MySQL timezone.
            $pdo->exec("SET time_zone = '+00:00'");
        }

        self::$connections[$name] = $pdo;

        return self::$connections[$name];
    }
}
