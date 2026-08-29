<?php

declare(strict_types=1);

/**
 * Resolves which of a form's fields should be shown as table/export
 * columns. Two sources, in priority order: an explicit ?cols[]= in the
 * current request (the on-page picker), falling back to a saved view's
 * configured column list (from ConfigStore), falling back to all fields.
 */
final class ColumnSelection
{
    public static function resolve(array $allFields, array $query): array
    {
        $selected = $query['cols'] ?? null;

        return self::filterByIds($allFields, is_array($selected) ? array_map('intval', $selected) : null);
    }

    /**
     * @param int[]|null $ids null/empty means "all fields"
     */
    public static function filterByIds(array $allFields, ?array $ids): array
    {
        if ($ids === null || empty($ids)) {
            return $allFields;
        }

        $filtered = array_values(array_filter(
            $allFields,
            fn (array $field) => in_array($field['id'], $ids, true)
        ));

        // If the saved/requested selection matched nothing (e.g. the form
        // changed since a view was saved), fall back to all columns
        // rather than showing an empty table.
        return empty($filtered) ? $allFields : $filtered;
    }
}
