<p><a href="index.php">&larr; All forms</a></p>
<h1><?= htmlspecialchars($formDef['label']) ?> &mdash; <?= htmlspecialchars($viewDef['label']) ?></h1>
<p class="subtitle">Grouped by: <strong><?= htmlspecialchars($groupLabel) ?></strong></p>

<div class="table-wrap">
<table>
<thead>
<tr><th><?= htmlspecialchars($groupLabel) ?></th><th>Entries</th></tr>
</thead>
<tbody>
<?php foreach ($groups as $group): ?>
<tr>
    <td>
        <a href="view.php?form=<?= (int) $formId ?>&view=<?= urlencode($viewSlug) ?>&value=<?= urlencode($group['value']) ?>">
            <?= htmlspecialchars($group['value']) ?>
        </a>
    </td>
    <td><?= (int) $group['total'] ?></td>
</tr>
<?php endforeach; ?>
<?php if (empty($groups)): ?>
<tr><td colspan="2">No values found for this field yet.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
