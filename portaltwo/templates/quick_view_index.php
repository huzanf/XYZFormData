<h1><?= htmlspecialchars($formDef['label']) ?></h1>
<p class="subtitle"><?= htmlspecialchars($activeQuickView['label']) ?> &mdash; grouped by <strong><?= htmlspecialchars($groupField['label']) ?></strong></p>

<div class="table-wrap">
<table>
<thead>
<tr><th><?= htmlspecialchars($groupField['label']) ?></th><th>Entries</th></tr>
</thead>
<tbody>
<?php foreach ($groups as $group):
    $valueParam = $groupField['filter_type'] === 'multi'
        ? 'f[' . (int) $groupField['id'] . '][]'
        : 'f[' . (int) $groupField['id'] . ']';
    $link = 'view.php?' . http_build_query([
        'form' => $formId,
        'qv'   => $activeQuickView['slug'],
    ]) . '&' . rawurlencode($valueParam) . '=' . urlencode($group['value']);
?>
<tr>
    <td><a href="<?= htmlspecialchars($link) ?>"><?= htmlspecialchars($group['value']) ?></a></td>
    <td><?= (int) $group['total'] ?></td>
</tr>
<?php endforeach; ?>
<?php if (empty($groups)): ?>
<tr><td colspan="2">No values yet.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
