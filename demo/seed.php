<?php

declare(strict_types=1);

/**
 * Builds demo/demo.sqlite with realistic sample data, shaped like the
 * Gravity Forms tables this portal reads (gf_form / gf_form_meta /
 * gf_entry / gf_entry_meta), so the real app code can run against it
 * without needing a live WordPress database.
 *
 * Run directly (php demo/seed.php) to (re)build the demo database, or it
 * is built automatically the first time demo/bootstrap.php runs.
 */

$dbFile = __DIR__ . '/demo.sqlite';

if (is_file($dbFile)) {
    unlink($dbFile);
}

$pdo = new PDO('sqlite:' . $dbFile, null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$pdo->exec('CREATE TABLE gf_form (id INTEGER PRIMARY KEY, title TEXT)');
$pdo->exec('CREATE TABLE gf_form_meta (form_id INTEGER, display_meta TEXT)');
$pdo->exec('CREATE TABLE gf_entry (id INTEGER PRIMARY KEY, form_id INTEGER, status TEXT, date_created TEXT)');
$pdo->exec('CREATE TABLE gf_entry_meta (entry_id INTEGER, form_id INTEGER, meta_key TEXT, meta_value TEXT)');

// ---------------------------------------------------------------------
// Form 1: Main Registration Form (accounting intake)
// ---------------------------------------------------------------------

$pdo->prepare('INSERT INTO gf_form (id, title) VALUES (1, ?)')->execute(['Main Registration Form']);

$mainFormMeta = [
    'fields' => [
        ['id' => 1, 'label' => 'Client Name', 'type' => 'text', 'inputs' => []],
        ['id' => 2, 'label' => 'Category', 'type' => 'select', 'inputs' => []],
        ['id' => 3, 'label' => 'Amount', 'type' => 'number', 'inputs' => []],
        ['id' => 4, 'label' => 'Payment Method', 'type' => 'select', 'inputs' => []],
        ['id' => 5, 'label' => 'Invoice #', 'type' => 'text', 'inputs' => []],
    ],
];
$pdo->prepare('INSERT INTO gf_form_meta (form_id, display_meta) VALUES (1, ?)')
    ->execute([json_encode($mainFormMeta)]);

$categories = ['Membership Dues', 'Event Ticket Sales', 'Donations', 'Merchandise Sales'];
$methods = ['Credit Card', 'Check', 'Bank Transfer', 'Cash'];
$clients = [
    'Alvarez Consulting', 'Brightside Bakery', 'Chen & Partners', 'Downtown Business Assoc.',
    'Evergreen Landscaping', 'Franklin Realty Group', 'Golden Gate Motors', 'Harper Legal Services',
    'Ivy Lane Studio', 'Johnson Family Trust', 'Kline Architecture', 'Lakeside Marina',
    'Miller & Sons Plumbing', 'Nova Tech Solutions', 'Oakwood Dental', 'Pearson Insurance',
    'Quintero Freight', 'Riverside Cafe', 'Sterling & Co.', 'Union Hardware',
];

$mainEntryId = 1;
$entryStmt = $pdo->prepare('INSERT INTO gf_entry (id, form_id, status, date_created) VALUES (?, 1, ?, ?)');
$metaStmt = $pdo->prepare('INSERT INTO gf_entry_meta (entry_id, form_id, meta_key, meta_value) VALUES (?, 1, ?, ?)');

foreach ($clients as $i => $client) {
    $date = sprintf('2026-%02d-%02d %02d:%02d:00', rand(1, 8), rand(1, 28), rand(8, 17), rand(0, 59));
    $entryStmt->execute([$mainEntryId, 'active', $date]);

    $category = $categories[$i % count($categories)];
    $amount = number_format(rand(50, 5000) + rand(0, 99) / 100, 2, '.', '');
    $method = $methods[array_rand($methods)];
    $invoice = 'INV-' . str_pad((string) (1000 + $i), 4, '0', STR_PAD_LEFT);

    $metaStmt->execute([$mainEntryId, '1', $client]);
    $metaStmt->execute([$mainEntryId, '2', $category]);
    $metaStmt->execute([$mainEntryId, '3', $amount]);
    $metaStmt->execute([$mainEntryId, '4', $method]);
    $metaStmt->execute([$mainEntryId, '5', $invoice]);

    $mainEntryId++;
}

// ---------------------------------------------------------------------
// Form 2: Event Registration Form
// ---------------------------------------------------------------------

$pdo->prepare('INSERT INTO gf_form (id, title) VALUES (2, ?)')->execute(['Event Registration Form']);

$eventFormMeta = [
    'fields' => [
        ['id' => 1, 'label' => 'Attendee Name', 'type' => 'text', 'inputs' => []],
        ['id' => 2, 'label' => 'Group Name', 'type' => 'text', 'inputs' => []],
        ['id' => 3, 'label' => 'Email', 'type' => 'text', 'inputs' => []],
        [
            'id' => 4,
            'label' => 'Events Participated',
            'type' => 'checkbox',
            'inputs' => [
                ['id' => '4.1', 'label' => 'Gala Dinner'],
                ['id' => '4.2', 'label' => 'Morning Workshop'],
                ['id' => '4.3', 'label' => 'Golf Outing'],
                ['id' => '4.4', 'label' => 'Silent Auction'],
            ],
        ],
        ['id' => 5, 'label' => 'Ticket Price', 'type' => 'number', 'inputs' => []],
    ],
];
$pdo->prepare('INSERT INTO gf_form_meta (form_id, display_meta) VALUES (2, ?)')
    ->execute([json_encode($eventFormMeta)]);

$groups = ['Rotary Club', 'Smith Family', 'Chamber of Commerce', 'Downtown Business Assoc.', 'Lakeside Marina Staff'];
$attendees = [
    'Maria Alvarez', 'James Brooks', 'Wei Chen', 'Priya Desai', 'Sam Evans',
    'Laura Franklin', 'Tomas Garcia', 'Nadia Haddad', 'Kevin Ito', 'Olivia Johnson',
    'David Kline', 'Emily Lawson', 'Marcus Nguyen', 'Sofia Pearson', 'Ben Quinn',
    'Rachel Silva',
];
$eventInputs = ['4.1' => 'Gala Dinner', '4.2' => 'Morning Workshop', '4.3' => 'Golf Outing', '4.4' => 'Silent Auction'];

$eventEntryId = 1000;
$entryStmt2 = $pdo->prepare('INSERT INTO gf_entry (id, form_id, status, date_created) VALUES (?, 2, ?, ?)');
$metaStmt2 = $pdo->prepare('INSERT INTO gf_entry_meta (entry_id, form_id, meta_key, meta_value) VALUES (?, 2, ?, ?)');

foreach ($attendees as $i => $name) {
    $date = sprintf('2026-%02d-%02d %02d:%02d:00', rand(1, 8), rand(1, 28), rand(8, 17), rand(0, 59));
    $entryStmt2->execute([$eventEntryId, 'active', $date]);

    $group = $groups[$i % count($groups)];
    $slug = strtolower(str_replace(' ', '.', $name));
    $email = $slug . '@example.com';
    $price = number_format(rand(35, 150), 2, '.', '');

    $metaStmt2->execute([$eventEntryId, '1', $name]);
    $metaStmt2->execute([$eventEntryId, '2', $group]);
    $metaStmt2->execute([$eventEntryId, '3', $email]);
    $metaStmt2->execute([$eventEntryId, '5', $price]);

    // Each attendee joins 1-3 events, so several show up under multiple event filters.
    $eventKeys = array_keys($eventInputs);
    shuffle($eventKeys);
    $joined = array_slice($eventKeys, 0, rand(1, 3));
    foreach ($joined as $inputKey) {
        $metaStmt2->execute([$eventEntryId, $inputKey, $eventInputs[$inputKey]]);
    }

    $eventEntryId++;
}

echo "Demo database seeded: {$dbFile}\n";
