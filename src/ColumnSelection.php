<?php

declare(strict_types=1);

/**
 * Resolves which of a form's fields should be shown as table/export
 * columns, based on the "Columns" picker in the filter panel (cols[]
 * query param). Shared by view.php and export.php so on-screen columns
 * and the exported file always match.
 */
final class ColumnSelection
{
    /**
     * @param array $allFields every field on the form (from FormRepository::getFields)
     * @param array $query usually $_GET
     * @return array the subset of $allFields to display, in original order
     */
    public static function resolve(array $allFields, array $query): array
    {
        $selected = $query['cols'] ?? null;
        if (!is_array($selected) || empty($selected)) {
            return $allFields;
        }

        $selectedIds = array_map('intval', $selected);
        $filtered = array_values(array_filter(
            $allFields,
            fn (array $field) => in_array($field['id'], $selectedIds, true)
        ));

        // If everything got unchecked (or the selection matched nothing),
        // fall back to showing all columns rather than an empty table.
        return empty($filtered) ? $allFields : $filtered;
    }
}
