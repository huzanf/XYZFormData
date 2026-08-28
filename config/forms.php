<?php

declare(strict_types=1);

/**
 * Maps each Gravity Form to the views this dashboard should offer.
 *
 * Top-level keys are Gravity Forms form IDs (see the "id" column in
 * wp_gf_form, or the form ID shown in the WP admin URL when editing a form).
 *
 * Each view is either:
 *   - a full data view: 'group_by' => null
 *     Shows every entry with every field as a column.
 *   - a grouped view: 'group_by' => <field ID>
 *     Shows an index of distinct values for that field (with entry counts),
 *     then a filtered, full-column table when you click into one value.
 *     Field IDs come from the same source as form IDs: open the form in the
 *     Gravity Forms editor and check the field's ID (shown when you hover
 *     over / click a field), or inspect the "fields" array in the
 *     wp_gf_form_meta.display_meta JSON for the form.
 *
 * Checkbox and multi-select fields naturally produce multiple grouping
 * values per entry (e.g. an attendee who joined two events shows up under
 * both event groups) — no extra config needed for that.
 *
 * PLACEHOLDER VALUES BELOW — replace the form IDs and field IDs with your
 * real ones before using this dashboard.
 */
return [
    // Example: event registration form.
    2 => [
        'label' => 'Event Registration Form',
        'views' => [
            'full' => [
                'label'    => 'Full Data',
                'group_by' => null,
            ],
            'by_group' => [
                'label'    => 'By Group Name',
                'group_by' => 5, // TODO: set to the "Group Name" field ID
            ],
            'by_event' => [
                'label'    => 'By Event Participated',
                'group_by' => 8, // TODO: set to the "Events Participated" field ID
            ],
        ],
    ],

    // Example: main intake/accounting form.
    1 => [
        'label' => 'Main Form',
        'views' => [
            'full' => [
                'label'    => 'Full Data',
                'group_by' => null,
            ],
            'by_category' => [
                'label'    => 'By Category',
                'group_by' => 3, // TODO: set to the "Category" field ID
            ],
        ],
    ],
];
