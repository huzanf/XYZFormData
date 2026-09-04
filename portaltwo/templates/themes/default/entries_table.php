<h1><?= htmlspecialchars($formDef['label']) ?></h1>
<?php if ($activeQuickView !== null): ?>
<p class="subtitle">
    <a href="view.php?form=<?= (int) $formId ?>&qv=<?= urlencode($activeQuickView['slug']) ?>">&larr; Back to <?= htmlspecialchars($activeQuickView['label']) ?> list</a>
</p>
<?php endif; ?>

<?php if ($hiddenMode): ?>
<p class="meta warning">Showing hidden entries only (<?= (int) $total ?>) — <a href="view.php?<?= htmlspecialchars(http_build_query(array_diff_key($_GET, ['hidden' => '']))) ?>">back to normal view</a></p>
<?php else: ?>
<p class="meta"><?= (int) $total ?> entr<?= $total === 1 ? 'y' : 'ies' ?> matching current filters
<?php if (Auth::isAdmin() && !empty($hiddenIds)): ?>
&middot; <a href="view.php?<?= htmlspecialchars(http_build_query(array_merge($_GET, ['hidden' => '1']))) ?>"><?= count($hiddenIds) ?> hidden</a>
<?php endif; ?>
</p>
<?php endif; ?>

<div class="table-wrap">
<table>
<thead>
<tr>
    <th>ID</th>
    <th>Date</th>
<?php foreach ($fields as $field): ?>
    <th><?= htmlspecialchars($field['label']) ?></th>
<?php endforeach; ?>
<?php if ($paymentConfig !== null && !empty($paymentConfig['enabled'])): ?>
    <th>Payment</th>
<?php endif; ?>
<?php if (Auth::isAdmin()): ?>
    <th></th>
<?php endif; ?>
</tr>
</thead>
<tbody>
<?php foreach ($entries as $entry):
    $entryId = (int) $entry['id'];
?>
<tr>
    <td><?= $entryId ?></td>
    <td><?= htmlspecialchars($entry['date_created']) ?></td>
<?php foreach ($fields as $field):
    $cell = $values[$entryId][$field['id']] ?? [];
?>
    <td><?= htmlspecialchars(implode(', ', $cell)) ?></td>
<?php endforeach; ?>
<?php if ($paymentConfig !== null && !empty($paymentConfig['enabled'])):
    $modeValue = implode(', ', $values[$entryId][(int) $paymentConfig['mode_field']] ?? []);
    $isOffline = $modeValue === $paymentConfig['offline_value'];
    $isPaidCash = $paidMap[$entryId] ?? false;
?>
    <td>
    <?php if ($isOffline): ?>
        <?php if ($isPaidCash): ?>
        Cash — Paid
        <?php if (Auth::isAdmin()): ?>
        <form method="post" action="entry_action.php" class="inline">
            <input type="hidden" name="action" value="mark_unpaid">
            <input type="hidden" name="form_id" value="<?= (int) $formId ?>">
            <input type="hidden" name="entry_id" value="<?= $entryId ?>">
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? ('view.php?form=' . $formId)) ?>">
            <button type="submit" class="link-button">Undo</button>
        </form>
        <?php endif; ?>
        <?php else: ?>
        Cash — Unpaid
        <?php if (Auth::isAdmin()): ?>
        <form method="post" action="entry_action.php" class="inline">
            <input type="hidden" name="action" value="mark_paid">
            <input type="hidden" name="form_id" value="<?= (int) $formId ?>">
            <input type="hidden" name="entry_id" value="<?= $entryId ?>">
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? ('view.php?form=' . $formId)) ?>">
            <button type="submit" class="link-button">Mark paid</button>
        </form>
        <?php endif; ?>
        <?php endif; ?>
    <?php else: ?>
        Online — Paid
    <?php endif; ?>
    </td>
<?php endif; ?>
<?php if (Auth::isAdmin()): ?>
    <td>
    <?php if ($hiddenMode): ?>
        <form method="post" action="entry_action.php" class="inline" onsubmit="return confirm('Unhide this entry?');">
            <input type="hidden" name="action" value="unhide">
            <input type="hidden" name="form_id" value="<?= (int) $formId ?>">
            <input type="hidden" name="entry_id" value="<?= $entryId ?>">
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? ('view.php?form=' . $formId)) ?>">
            <button type="submit" class="link-button">Unhide</button>
        </form>
    <?php else: ?>
        <form method="post" action="entry_action.php" class="inline" onsubmit="return confirm('Hide this entry? It will no longer appear in any view or export, but nothing in WordPress is touched.');">
            <input type="hidden" name="action" value="hide">
            <input type="hidden" name="form_id" value="<?= (int) $formId ?>">
            <input type="hidden" name="entry_id" value="<?= $entryId ?>">
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? ('view.php?form=' . $formId)) ?>">
            <button type="submit" class="link-button danger">Hide</button>
        </form>
    <?php endif; ?>
    </td>
<?php endif; ?>
</tr>
<?php endforeach; ?>
<?php if (empty($entries)): ?>
<?php $paymentColumnShown = $paymentConfig !== null && !empty($paymentConfig['enabled']); ?>
<tr><td colspan="<?= 2 + count($fields) + ($paymentColumnShown ? 1 : 0) + (Auth::isAdmin() ? 1 : 0) ?>">No entries match these filters.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>

<?php
$totalPages = (int) ceil($total / $perPage);
if ($totalPages > 1):
    $baseParams = $_GET;
    unset($baseParams['page']);
    $baseUrl = 'view.php?' . http_build_query($baseParams);
?>
<nav class="pagination">
    <?php if ($page > 1): ?><a href="<?= htmlspecialchars($baseUrl) ?>&page=<?= $page - 1 ?>">&larr; Prev</a><?php endif; ?>
    <span>Page <?= $page ?> of <?= $totalPages ?></span>
    <?php if ($page < $totalPages): ?><a href="<?= htmlspecialchars($baseUrl) ?>&page=<?= $page + 1 ?>">Next &rarr;</a><?php endif; ?>
</nav>
<?php endif; ?>
