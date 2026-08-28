<?php

declare(strict_types=1);

/**
 * Which Gravity Forms forms this portal exposes. That's all this file
 * needs — the full data view, every field's filter control, and the
 * Excel export are all generated dynamically from each form's actual
 * field definitions, so a different form can have a completely different
 * column set with no extra config here.
 *
 * Keys are Gravity Forms form IDs (see the "id" column in wp_gf_form, or
 * the form ID shown in the WP admin URL when editing a form).
 *
 * PLACEHOLDER VALUES BELOW — replace with your real form IDs and labels.
 */
return [
    1 => ['label' => 'Main Registration Form'],
    2 => ['label' => 'Event Registration Form'],
    // Add more forms here, e.g.:
    // 3 => ['label' => 'Volunteer Sign-up Form'],
];
