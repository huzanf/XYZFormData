<?php

declare(strict_types=1);

/**
 * Builds the derived, pivoted "sheet" views defined per form in
 * config/sheets.php — replacing the manual Excel work of collating a
 * "Complete Table / Group Sheet / Member Category X / Event List" set of
 * tabs by hand from a Gravity Forms export. Used identically by sheets.php
 * (on-screen) and its multi-sheet Excel export, so both always agree.
 *
 * Every sheet definition builds down to a normalized shape:
 *   ['label' => string, 'layout' => 'flat'|'stacked'|'side_by_side', 'blocks' => [...]]
 *
 * - layout 'flat':         exactly one block, rendered as a single table.
 * - layout 'stacked':      each block is its own heading + full-width
 *                           table, one below the next (e.g. one table per
 *                           Member Category value).
 * - layout 'side_by_side': each block is its own mini-table, placed left
 *                           to right (e.g. one column-group per Group
 *                           value, or per Event field).
 *
 * Each block is ['heading' => ?string, 'headers' => string[], 'rows' => string[][]].
 */
final class SheetBuilder
{
    public function __construct(private EntryRepository $entryRepo)
    {
    }

    /**
     * @param array $fields  this form's fields, from FormRepository::getFields()
     * @param array $entries all entries for this form (id, date_created, status)
     * @param array $values  entry_id => field_id => string[], from EntryRepository::getValuesForEntries()
     * @param array $sheetDef one entry from config/sheets.php's 'sheets' list
     */
    public function build(int $formId, array $fields, array $entries, array $values, array $sheetDef): array
    {
        return match ($sheetDef['type']) {
            'complete'         => $this->buildComplete($fields, $entries, $values, $sheetDef),
            'group_columns'    => $this->buildGroupColumns($formId, $fields, $entries, $values, $sheetDef),
            'value_sections'   => $this->buildValueSections($formId, $fields, $entries, $values, $sheetDef),
            'presence_columns' => $this->buildPresenceColumns($fields, $entries, $values, $sheetDef),
            default            => throw new InvalidArgumentException("Unknown sheet type '{$sheetDef['type']}'."),
        };
    }

    private function buildComplete(array $fields, array $entries, array $values, array $sheetDef): array
    {
        $columns = $this->resolveColumns($fields, $sheetDef['columns'] ?? null);

        $headers = ['ID', 'Date'];
        foreach ($columns as $field) {
            $headers[] = $field['label'];
        }

        $rows = [];
        foreach ($entries as $entry) {
            $row = [(string) $entry['id'], (string) $entry['date_created']];
            foreach ($columns as $field) {
                $row[] = $this->cell($values, (int) $entry['id'], $field['id']);
            }
            $rows[] = $row;
        }

        return [
            'label'  => $sheetDef['label'] ?? 'Complete Table',
            'layout' => 'flat',
            'blocks' => [['heading' => null, 'headers' => $headers, 'rows' => $rows]],
        ];
    }

    /**
     * One side-by-side block per distinct value of the group_by field.
     * columns[0]'s own label is replaced by the group's value in the
     * header (matching the mock-up: a "DD" column holding that group's
     * member names); any further columns keep their normal field labels.
     */
    private function buildGroupColumns(int $formId, array $fields, array $entries, array $values, array $sheetDef): array
    {
        $groupField = $this->field($fields, (int) $sheetDef['group_by']);
        $columns = $this->resolveColumns($fields, $sheetDef['columns']);
        $groupValues = $this->resolveFieldValues($formId, $groupField);

        $blocks = [];
        foreach ($groupValues as $groupValue) {
            $headers = ['Sr.', $groupValue];
            foreach (array_slice($columns, 1) as $field) {
                $headers[] = $field['label'];
            }

            $rows = [];
            $sr = 1;
            foreach ($entries as $entry) {
                $entryId = (int) $entry['id'];
                if ($this->cell($values, $entryId, $groupField['id']) !== $groupValue) {
                    continue;
                }

                $row = [(string) $sr];
                foreach ($columns as $field) {
                    $row[] = $this->cell($values, $entryId, $field['id']);
                }
                $rows[] = $row;
                $sr++;
            }

            $blocks[] = ['heading' => $groupValue, 'headers' => $headers, 'rows' => $rows];
        }

        return ['label' => $sheetDef['label'] ?? 'Group Sheet', 'layout' => 'side_by_side', 'blocks' => $blocks];
    }

    /**
     * One stacked, full-width table per distinct value of category_by
     * (e.g. "Member Category Junior", "Member Category Parent"). A value
     * with no matching entries still gets its own empty table, as long as
     * it's one of the field's configured Gravity Forms choices.
     */
    private function buildValueSections(int $formId, array $fields, array $entries, array $values, array $sheetDef): array
    {
        $categoryField = $this->field($fields, (int) $sheetDef['category_by']);
        $columns = $this->resolveColumns($fields, $sheetDef['columns']);
        $categoryValues = $this->resolveFieldValues($formId, $categoryField);
        $sectionLabel = $sheetDef['label'] ?? 'Category';

        $blocks = [];
        foreach ($categoryValues as $categoryValue) {
            $headers = array_map(static fn (array $f) => $f['label'], $columns);

            $rows = [];
            foreach ($entries as $entry) {
                $entryId = (int) $entry['id'];
                if ($this->cell($values, $entryId, $categoryField['id']) !== $categoryValue) {
                    continue;
                }

                $row = [];
                foreach ($columns as $field) {
                    $row[] = $this->cell($values, $entryId, $field['id']);
                }
                $rows[] = $row;
            }

            $blocks[] = ['heading' => $sectionLabel . ' ' . $categoryValue, 'headers' => $headers, 'rows' => $rows];
        }

        return ['label' => $sectionLabel, 'layout' => 'stacked', 'blocks' => $blocks];
    }

    /**
     * One side-by-side block per field in `fields` (e.g. Event1, Event2,
     * Event3) — every entry where that field is non-blank gets a row.
     * columns[0] is shown under the presence field's own label (e.g. the
     * attendee's name); any further columns keep their normal field labels.
     */
    private function buildPresenceColumns(array $fields, array $entries, array $values, array $sheetDef): array
    {
        $columns = $this->resolveColumns($fields, $sheetDef['columns']);

        $blocks = [];
        foreach ($sheetDef['fields'] as $presenceFieldId) {
            $presenceField = $this->field($fields, (int) $presenceFieldId);

            $headers = [$presenceField['label']];
            foreach (array_slice($columns, 1) as $field) {
                $headers[] = $field['label'];
            }

            $rows = [];
            foreach ($entries as $entry) {
                $entryId = (int) $entry['id'];
                if ($this->cell($values, $entryId, $presenceField['id']) === '') {
                    continue;
                }

                $row = [];
                foreach ($columns as $field) {
                    $row[] = $this->cell($values, $entryId, $field['id']);
                }
                $rows[] = $row;
            }

            $blocks[] = ['heading' => $presenceField['label'], 'headers' => $headers, 'rows' => $rows];
        }

        return ['label' => $sheetDef['label'] ?? 'Presence', 'layout' => 'side_by_side', 'blocks' => $blocks];
    }

    /**
     * A field's possible values: its configured Gravity Forms choices when
     * it has any (so an empty category still shows up), else whatever
     * distinct values actually appear in the data.
     *
     * @return string[]
     */
    private function resolveFieldValues(int $formId, array $field): array
    {
        if (!empty($field['choices'])) {
            return $field['choices'];
        }

        return array_column($this->entryRepo->getDistinctValues($formId, $field['id']), 'value');
    }

    /**
     * @param int[]|null $columnIds null means every field, in form order
     * @return array[]
     */
    private function resolveColumns(array $fields, ?array $columnIds): array
    {
        if ($columnIds === null) {
            return $fields;
        }

        $byId = [];
        foreach ($fields as $field) {
            $byId[$field['id']] = $field;
        }

        $columns = [];
        foreach ($columnIds as $id) {
            if (isset($byId[$id])) {
                $columns[] = $byId[$id];
            }
        }

        return $columns;
    }

    private function field(array $fields, int $id): array
    {
        foreach ($fields as $field) {
            if ($field['id'] === $id) {
                return $field;
            }
        }

        throw new InvalidArgumentException("config/sheets.php refers to unknown field id {$id}.");
    }

    private function cell(array $values, int $entryId, int $fieldId): string
    {
        return implode(', ', $values[$entryId][$fieldId] ?? []);
    }
}
