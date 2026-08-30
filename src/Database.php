<?php

declare(strict_types=1);

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

        self::$connections[$name] = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return self::$connections[$name];
    }
}
