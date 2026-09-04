<?php

declare(strict_types=1);

/**
 * Form list + quick views for the seeded demo data (see demo/seed.php).
 * Same shape as config/forms.php in the real app.
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
];
