<?php

declare(strict_types=1);

/**
 * Per-form "Sheets" definitions — the derived/pivoted breakdowns shown on
 * sheets.php and included in its multi-sheet Excel export. Replaces the
 * manual work of collating "Complete Table / Group Sheet / Member
 * Category X / Event List" tabs by hand from a Gravity Forms export.
 *
 * Keyed by Gravity Forms form ID (same IDs as config/forms.php). A form
 * with no entry here simply has no "Sheets" tab.
 *
 * Each form's fields are different, so every sheet is built from field
 * IDs — open the form in the Gravity Forms editor to find them, or inspect
 * the "fields" array in wp_gf_form_meta.display_meta for the form.
 *
 * Sheet definitions:
 *
 *   ['type' => 'complete', 'label' => string, 'columns' => int[]|null]
 *     The raw entries table. `columns` is a list of field IDs to include,
 *     in order; null means every field.
 *
 *   ['type' => 'group_columns', 'label' => string, 'group_by' => int, 'columns' => int[]]
 *     One side-by-side block per distinct value of the group_by field.
 *     `columns[0]` is shown under a header of that group's value (e.g. the
 *     member's name, under a "DD"/"CS" column); any further columns keep
 *     their own field labels (e.g. an ID column). Matches the mock-up's
 *     "Group Sheet".
 *
 *   ['type' => 'value_sections', 'label' => string, 'category_by' => int, 'columns' => int[]]
 *     One stacked, full-width table per distinct value of category_by
 *     (e.g. "Member Category Junior", "Member Category Parent"). A value
 *     with no matching entries still gets its own empty table, as long as
 *     it's one of the field's configured choices in Gravity Forms.
 *
 *   ['type' => 'presence_columns', 'label' => string, 'fields' => int[], 'columns' => int[]]
 *     One side-by-side block per field ID in `fields` (e.g. Event1,
 *     Event2, Event3) — every entry where that field is non-blank gets a
 *     row, shown under `columns` (e.g. Name, Group). Matches the mock-up's
 *     "Event List".
 *
 * PLACEHOLDER VALUES BELOW — the field IDs are examples only, matching the
 * shape of the "Form 1" / "Form 2" mock-ups. Replace them with your real
 * form and field IDs before relying on this.
 */
return [
    // Example matching "Form 1": Membership Number, Name, Group, Member
    // Category, Address, ID, Phone.
    1 => [
        'sheets' => [
            ['type' => 'complete', 'label' => 'Complete Table', 'columns' => null],
            ['type' => 'group_columns', 'label' => 'Group Sheet', 'group_by' => 3, 'columns' => [2, 6]],
            ['type' => 'value_sections', 'label' => 'Member Category', 'category_by' => 4, 'columns' => [3, 2, 6]],
        ],
    ],

    // Example matching "Form 2": adds an Event List on top of the same
    // Group Sheet / Member Category pattern.
    2 => [
        'sheets' => [
            ['type' => 'complete', 'label' => 'Complete Table', 'columns' => null],
            ['type' => 'group_columns', 'label' => 'Group Sheet', 'group_by' => 3, 'columns' => [2, 6]],
            ['type' => 'value_sections', 'label' => 'Member Category', 'category_by' => 4, 'columns' => [3, 2, 6]],
            ['type' => 'presence_columns', 'label' => 'Event List', 'fields' => [7, 8, 9], 'columns' => [2, 3]],
        ],
    ],

    // Add more forms here, or omit a form entirely if it doesn't need a
    // Sheets tab.
];
