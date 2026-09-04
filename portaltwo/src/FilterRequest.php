<?php

declare(strict_types=1);

/**
 * Turns the $_GET query string from the filter panel into a list of filter
 * specs that EntryRepository can apply. Shared by view.php (paginated
 * on-screen results) and export.php (the full matching set as .xlsx), so
 * both always filter identically.
 */
final class FilterRequest
{
    /**
     * @param array $fields  form fields from FormRepository::getFields()
     * @param array $query   usually $_GET
     * @return array<int, array<string, mixed>> filter specs
     */
    public static function parse(array $fields, array $query): array
    {
        $filters = [];

        $dateFrom = trim((string) ($query['date_from'] ?? ''));
        $dateTo = trim((string) ($query['date_to'] ?? ''));
        if ($dateFrom !== '' || $dateTo !== '') {
            $filters[] = [
                'kind' => 'date_created',
                'from' => $dateFrom !== '' ? $dateFrom : null,
                'to'   => $dateTo !== '' ? $dateTo : null,
            ];
        }

        $raw = is_array($query['f'] ?? null) ? $query['f'] : [];

        foreach ($fields as $field) {
            $fieldId = $field['id'];
            $value = $raw[$fieldId] ?? null;

            switch ($field['filter_type']) {
                case 'text':
                    $text = trim((string) (is_scalar($value) ? $value : ''));
                    if ($text !== '') {
                        $filters[] = ['kind' => 'text', 'field_id' => $fieldId, 'value' => $text];
                    }
                    break;

                case 'choice':
                    $choice = trim((string) (is_scalar($value) ? $value : ''));
                    if ($choice !== '') {
                        $filters[] = ['kind' => 'choice', 'field_id' => $fieldId, 'value' => $choice];
                    }
                    break;

                case 'multi':
                    $values = is_array($value)
                        ? array_values(array_filter(array_map('strval', $value), fn ($v) => $v !== ''))
                        : [];
                    if (!empty($values)) {
                        $filters[] = ['kind' => 'multi', 'field_id' => $fieldId, 'values' => $values];
                    }
                    break;

                case 'range':
                    $minRaw = is_array($value) ? ($value['min'] ?? '') : '';
                    $maxRaw = is_array($value) ? ($value['max'] ?? '') : '';
                    $min = is_numeric($minRaw) ? (float) $minRaw : null;
                    $max = is_numeric($maxRaw) ? (float) $maxRaw : null;
                    if ($min !== null || $max !== null) {
                        $filters[] = ['kind' => 'range', 'field_id' => $fieldId, 'min' => $min, 'max' => $max];
                    }
                    break;
            }
        }

        return $filters;
    }
}
