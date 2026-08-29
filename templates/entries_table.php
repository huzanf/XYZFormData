<h1><?= htmlspecialchars($formDef['label']) ?></h1>
<?php if ($activeQuickView !== null): ?>
<p class="subtitle">
    <a href="view.php?form=<?= (int) $formId ?>&qv=<?= urlencode($activeQuickView['slug']) ?>">&larr; Back to <?= htmlspecialchars($activeQuickView['label']) ?> list</a>
</p>
<?php endif; ?>
<p class="meta"><?= (int) $total ?> entr<?= $total === 1 ? 'y' : 'ies' ?> matching current filters</p>

<div class="table-wrap">
<table>
<thead>
<tr>
    <th>ID</th>
    <th>Date</th>
<?php foreach ($fields as $field): ?>
    <th><?= htmlspecialchars($field['label']) ?></th>
<?php endforeach; ?>
</tr>
</thead>
<tbody>
<?php foreach ($entries as $entry): ?>
<tr>
    <td><?= (int) $entry['id'] ?></td>
    <td><?= htmlspecialchars($entry['date_created']) ?></td>
<?php foreach ($fields as $field):
    $cell = $values[(int) $entry['id']][$field['id']] ?? [];
?>
    <td><?= htmlspecialchars(implode(', ', $cell)) ?></td>
<?php endforeach; ?>
</tr>
<?php endforeach; ?>
<?php if (empty($entries)): ?>
<tr><td colspan="<?= 2 + count($fields) ?>">No entries match these filters.</td></tr>
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
