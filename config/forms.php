<?php

declare(strict_types=1);

/**
 * Which Gravity Forms forms this portal exposes, and any named "quick
 * views" for each — shortcuts that browse a form grouped by one field
 * (e.g. by membership type, by group) before drilling into a filtered
 * table. Every form always also gets a default "Full Data" view with no
 * extra config needed.
 *
 * The full data view, every field's filter control, the column picker,
 * and the Excel export are all generated dynamically from each form's
 * actual field definitions — quick_views only needs to name which field
 * to group by, nothing about the form's other columns.
 *
 * Keys are Gravity Forms form IDs (see the "id" column in wp_gf_form, or
 * the form ID shown in the WP admin URL when editing a form). Each quick
 * view's field_id is the Gravity Forms field ID to group by — open the
 * form in the Gravity Forms editor and check the field's ID, or inspect
 * the "fields" array in wp_gf_form_meta.display_meta (JSON) for the form.
 *
 * PLACEHOLDER VALUES BELOW — replace with your real form IDs, field IDs,
 * and labels.
 */
return [
    1 => [
        'label' => 'Yearly Membership Form',
        'quick_views' => [
            ['slug' => 'membership_type', 'label' => 'By Membership Type', 'field_id' => 2],
            ['slug' => 'group', 'label' => 'By Group', 'field_id' => 6],
        ],
    ],

    2 => [
        'label' => 'Event Registration Form',
        'quick_views' => [
            ['slug' => 'event_type', 'label' => 'By Event Type', 'field_id' => 4],
            ['slug' => 'group', 'label' => 'By Group', 'field_id' => 2],
        ],
    ],

    // Add more forms here. quick_views is optional — omit it for a form
    // that should only have the default Full Data view, e.g.:
    // 3 => ['label' => 'Volunteer Sign-up Form'],
];
