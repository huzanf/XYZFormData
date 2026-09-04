<?php

declare(strict_types=1);

/**
 * Sheet definitions for the seeded demo data (see demo/seed.php). Same
 * shape as config/sheets.php in the real app — seeds demo/sheets_store.json
 * on first use, same migrate-once pattern as SheetConfigStore.
 */
return [
    1 => [
        'sheets' => [
            ['type' => 'complete', 'label' => 'Complete Table', 'columns' => null],
            ['type' => 'group_columns', 'label' => 'Group Sheet', 'group_by' => 6, 'columns' => [1, 5]],
            ['type' => 'value_sections', 'label' => 'Member Category', 'category_by' => 2, 'columns' => [6, 1, 3]],
        ],
    ],
    2 => [
        'sheets' => [
            ['type' => 'complete', 'label' => 'Complete Table', 'columns' => null],
            ['type' => 'group_columns', 'label' => 'Group Sheet', 'group_by' => 2, 'columns' => [1, 3]],
            ['type' => 'presence_columns', 'label' => 'Event List', 'fields' => [7, 8, 9], 'columns' => [1, 2]],
        ],
    ],
];
