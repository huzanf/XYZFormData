<?php

declare(strict_types=1);

/**
 * View config for the seeded demo data (see demo/seed.php). Mirrors the
 * shape of config/forms.php in the real app, just pointed at the field IDs
 * the demo data actually uses.
 */
return [
    2 => [
        'label' => 'Event Registration Form',
        'views' => [
            'full' => [
                'label'    => 'Full Data',
                'group_by' => null,
            ],
            'by_group' => [
                'label'    => 'By Group Name',
                'group_by' => 2,
            ],
            'by_event' => [
                'label'    => 'By Event Participated',
                'group_by' => 4,
            ],
        ],
    ],

    1 => [
        'label' => 'Main Form',
        'views' => [
            'full' => [
                'label'    => 'Full Data',
                'group_by' => null,
            ],
            'by_category' => [
                'label'    => 'By Category',
                'group_by' => 2,
            ],
        ],
    ],
];
