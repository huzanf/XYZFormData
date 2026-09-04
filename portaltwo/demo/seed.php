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

$pdo->exec('CREATE TABLE gf_form (id INTEGER PRIMARY KEY, title TEXT, date_created TEXT)');
$pdo->exec('CREATE TABLE gf_form_meta (form_id INTEGER, display_meta TEXT)');
$pdo->exec('CREATE TABLE gf_entry (id INTEGER PRIMARY KEY, form_id INTEGER, status TEXT, date_created TEXT, payment_status TEXT, transaction_id TEXT)');
$pdo->exec('CREATE TABLE gf_entry_meta (entry_id INTEGER, form_id INTEGER, meta_key TEXT, meta_value TEXT)');

// ---------------------------------------------------------------------
// Form 1: Yearly Membership Form (created 2025 — older, so it should
// sort BELOW the Event Registration Form on the "latest first" list)
// ---------------------------------------------------------------------

$pdo->prepare('INSERT INTO gf_form (id, title, date_created) VALUES (1, ?, ?)')
    ->execute(['Yearly Membership Form', '2025-01-15 09:00:00']);

$membershipFormMeta = [
    'fields' => [
        ['id' => 1, 'label' => 'Member Name', 'type' => 'text', 'inputs' => []],
        [
            'id' => 2, 'label' => 'Membership Type', 'type' => 'select', 'inputs' => [],
            // 'Honorary' has no matching entries below, on purpose — it
            // still gets its own Member Category sheet, empty, since it's
            // one of this field's configured choices.
            'choices' => [
                ['text' => 'Individual', 'value' => 'Individual'],
                ['text' => 'Family', 'value' => 'Family'],
                ['text' => 'Student', 'value' => 'Student'],
                ['text' => 'Lifetime', 'value' => 'Lifetime'],
                ['text' => 'Honorary', 'value' => 'Honorary'],
            ],
        ],
        ['id' => 3, 'label' => 'Amount', 'type' => 'number', 'inputs' => []],
        ['id' => 4, 'label' => 'Payment Method', 'type' => 'select', 'inputs' => []],
        ['id' => 5, 'label' => 'Invoice #', 'type' => 'text', 'inputs' => []],
        ['id' => 6, 'label' => 'Group', 'type' => 'text', 'inputs' => []],
    ],
];
$pdo->prepare('INSERT INTO gf_form_meta (form_id, display_meta) VALUES (1, ?)')
    ->execute([json_encode($membershipFormMeta)]);

$membershipTypes = ['Individual', 'Family', 'Student', 'Lifetime'];
$methods = ['Credit Card', 'Check', 'Bank Transfer', 'Cash'];
$groups = ['Downtown Chapter', 'Uptown Chapter', 'Corporate Members', 'Youth Wing'];
$members = [
    'Maria Alvarez', 'James Brooks', 'Wei Chen', 'Priya Desai', 'Sam Evans',
    'Laura Franklin', 'Tomas Garcia', 'Nadia Haddad', 'Kevin Ito', 'Olivia Johnson',
    'David Kline', 'Emily Lawson', 'Marcus Nguyen', 'Sofia Pearson', 'Ben Quinn',
    'Rachel Silva', 'Chen & Partners', 'Sterling & Co.', 'Union Hardware', 'Riverside Cafe',
];

$memberEntryId = 1;
$entryStmt = $pdo->prepare('INSERT INTO gf_entry (id, form_id, status, date_created) VALUES (?, 1, ?, ?)');
$metaStmt = $pdo->prepare('INSERT INTO gf_entry_meta (entry_id, form_id, meta_key, meta_value) VALUES (?, 1, ?, ?)');

foreach ($members as $i => $member) {
    $date = sprintf('2026-%02d-%02d %02d:%02d:00', rand(1, 8), rand(1, 28), rand(8, 17), rand(0, 59));
    $entryStmt->execute([$memberEntryId, 'active', $date]);

    $type = $membershipTypes[$i % count($membershipTypes)];
    $amount = number_format(rand(25, 500) + rand(0, 99) / 100, 2, '.', '');
    $method = $methods[array_rand($methods)];
    $invoice = 'INV-' . str_pad((string) (1000 + $i), 4, '0', STR_PAD_LEFT);
    $group = $groups[$i % count($groups)];

    $metaStmt->execute([$memberEntryId, '1', $member]);
    $metaStmt->execute([$memberEntryId, '2', $type]);
    $metaStmt->execute([$memberEntryId, '3', $amount]);
    $metaStmt->execute([$memberEntryId, '4', $method]);
    $metaStmt->execute([$memberEntryId, '5', $invoice]);
    $metaStmt->execute([$memberEntryId, '6', $group]);

    $memberEntryId++;
}

// ---------------------------------------------------------------------
// Form 2: Event Registration Form (created 2026 — newer, so it should
// sort ABOVE the Yearly Membership Form on the "latest first" list)
// ---------------------------------------------------------------------

$pdo->prepare('INSERT INTO gf_form (id, title, date_created) VALUES (2, ?, ?)')
    ->execute(['Event Registration Form', '2026-06-01 09:00:00']);

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
        ['id' => 6, 'label' => 'Payment Method', 'type' => 'select', 'inputs' => []],
        // Independent, sometimes-blank fields — the "Event List" sheet
        // type (presence_columns) pivots on these, one block per field.
        ['id' => 7, 'label' => 'Event1', 'type' => 'text', 'inputs' => []],
        ['id' => 8, 'label' => 'Event2', 'type' => 'text', 'inputs' => []],
        ['id' => 9, 'label' => 'Event3', 'type' => 'text', 'inputs' => []],
    ],
];
$pdo->prepare('INSERT INTO gf_form_meta (form_id, display_meta) VALUES (2, ?)')
    ->execute([json_encode($eventFormMeta)]);

$eventGroups = ['Rotary Club', 'Smith Family', 'Chamber of Commerce', 'Downtown Business Assoc.', 'Lakeside Marina Staff'];
$attendees = [
    'Maria Alvarez', 'James Brooks', 'Wei Chen', 'Priya Desai', 'Sam Evans',
    'Laura Franklin', 'Tomas Garcia', 'Nadia Haddad', 'Kevin Ito', 'Olivia Johnson',
    'David Kline', 'Emily Lawson', 'Marcus Nguyen', 'Sofia Pearson', 'Ben Quinn',
    'Rachel Silva',
];
$eventInputs = ['4.1' => 'Gala Dinner', '4.2' => 'Morning Workshop', '4.3' => 'Golf Outing', '4.4' => 'Silent Auction'];

$eventEntryId = 1000;
$entryStmt2 = $pdo->prepare(
    'INSERT INTO gf_entry (id, form_id, status, date_created, payment_status, transaction_id) VALUES (?, 2, ?, ?, ?, ?)'
);
$metaStmt2 = $pdo->prepare('INSERT INTO gf_entry_meta (entry_id, form_id, meta_key, meta_value) VALUES (?, 2, ?, ?)');

foreach ($attendees as $i => $name) {
    $date = sprintf('2026-%02d-%02d %02d:%02d:00', rand(1, 8), rand(1, 28), rand(8, 17), rand(0, 59));

    // Mirrors real data: most online payments succeed, a couple are
    // abandoned (blank status/no transaction id) and stay hidden once
    // payment filtering is enabled; every third attendee paid by cash.
    $isCash = $i % 3 === 0;
    $isAbandoned = !$isCash && $i % 7 === 3;
    $paymentMethod = $isCash ? 'Cash' : 'Online';
    $paymentStatus = $isCash ? '' : ($isAbandoned ? '' : 'Paid');
    $transactionId = $isCash ? '' : ($isAbandoned ? '' : 'TXN-' . (5000 + $i));

    $entryStmt2->execute([$eventEntryId, 'active', $date, $paymentStatus, $transactionId]);

    $group = $eventGroups[$i % count($eventGroups)];
    $slug = strtolower(str_replace(' ', '.', $name));
    $email = $slug . '@example.com';
    $price = number_format(rand(35, 150), 2, '.', '');

    $metaStmt2->execute([$eventEntryId, '1', $name]);
    $metaStmt2->execute([$eventEntryId, '2', $group]);
    $metaStmt2->execute([$eventEntryId, '3', $email]);
    $metaStmt2->execute([$eventEntryId, '5', $price]);
    $metaStmt2->execute([$eventEntryId, '6', $paymentMethod]);

    // Each attendee joins 1-3 events, so several show up under multiple event-type filters.
    $eventKeys = array_keys($eventInputs);
    shuffle($eventKeys);
    $joined = array_slice($eventKeys, 0, rand(1, 3));
    foreach ($joined as $inputKey) {
        $metaStmt2->execute([$eventEntryId, $inputKey, $eventInputs[$inputKey]]);
    }

    // Event1/2/3: each attendee independently registered for 0-3 of them.
    foreach ([7, 8, 9] as $eventFieldId) {
        if (rand(0, 1) === 1) {
            $metaStmt2->execute([$eventEntryId, (string) $eventFieldId, 'EV' . rand(100, 999)]);
        }
    }

    $eventEntryId++;
}

echo "Demo database seeded: {$dbFile}\n";
