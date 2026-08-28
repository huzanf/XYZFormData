<?php

declare(strict_types=1);

/**
 * Resolves the Gravity Forms table names for this WordPress install.
 * Targets the current Gravity Forms schema (2.3+): gf_form / gf_form_meta /
 * gf_entry / gf_entry_meta. Pre-2.3 installs used rg_lead / rg_form tables
 * with a different layout and are not supported here.
 */
final class SchemaDetector
{
    private static ?array $tables = null;

    public static function tables(PDO $pdo, string $prefix, string $dbName): array
    {
        if (self::$tables !== null) {
            return self::$tables;
        }

        $entryTable = $prefix . 'gf_entry';

        if (!self::tableExists($pdo, $dbName, $entryTable)) {
            throw new RuntimeException(
                "Could not find table '{$entryTable}' in database '{$dbName}'. " .
                "Check DB_TABLE_PREFIX in .env. If this WordPress site runs a Gravity " .
                "Forms version older than 2.3, it uses the legacy rg_lead/rg_form " .
                "tables, which this dashboard does not currently read."
            );
        }

        self::$tables = [
            'form'       => $prefix . 'gf_form',
            'form_meta'  => $prefix . 'gf_form_meta',
            'entry'      => $prefix . 'gf_entry',
            'entry_meta' => $prefix . 'gf_entry_meta',
        ];

        return self::$tables;
    }

    private static function tableExists(PDO $pdo, string $dbName, string $table): bool
    {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = ?'
        );
        $stmt->execute([$dbName, $table]);

        return (int) $stmt->fetchColumn() > 0;
    }
}
