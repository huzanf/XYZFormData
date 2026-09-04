<h1><?= htmlspecialchars($formEntry['label']) ?></h1>
<p class="meta">
    <a href="manage.php">&larr; All forms</a> &middot;
    <a href="view.php?form=<?= (int) $formId ?>">View this form's data &rarr;</a>
</p>

<?php if ($fieldsError !== null): ?>
<p class="meta warning">Could not load this form's fields from the database: <?= htmlspecialchars($fieldsError) ?></p>
<?php endif; ?>

<h2>Views</h2>
<div class="table-wrap">
<table>
<thead><tr><th>Label</th><th>Group by</th><th>Columns</th><th></th></tr></thead>
<tbody>
<?php foreach ($views as $view):
    $groupLabel = '—';
    foreach ($allFields as $f) {
        if ($f['id'] === $view['group_by']) {
            $groupLabel = $f['label'];
            break;
        }
    }
    $columnsLabel = empty($view['columns']) ? 'All' : count($view['columns']) . ' selected';
?>
<tr>
    <td><?= htmlspecialchars($view['label']) ?></td>
    <td><?= htmlspecialchars($groupLabel) ?></td>
    <td><?= htmlspecialchars($columnsLabel) ?></td>
    <td>
        <a href="manage.php?form=<?= (int) $formId ?>&edit=<?= urlencode($view['slug']) ?>">Edit</a>
        &middot;
        <form method="post" class="inline" onsubmit="return confirm('Delete this view?');">
            <input type="hidden" name="action" value="delete_view">
            <input type="hidden" name="form_id" value="<?= (int) $formId ?>">
            <input type="hidden" name="slug" value="<?= htmlspecialchars($view['slug']) ?>">
            <button type="submit" class="link-button danger">Delete</button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
<?php if (empty($views)): ?>
<tr><td colspan="4">No named views yet — the default Full Data view is always available.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>

<?php
$editingSlug = $_GET['edit'] ?? null;
$editing = null;
foreach ($views as $view) {
    if ($view['slug'] === $editingSlug) {
        $editing = $view;
        break;
    }
}
$editingColumns = $editing['columns'] ?? [];
?>

<h2><?= $editing !== null ? 'Edit view' : 'Add a view' ?></h2>
<form method="post" class="manage-view-form">
    <input type="hidden" name="action" value="save_view">
    <input type="hidden" name="form_id" value="<?= (int) $formId ?>">
    <?php if ($editing !== null): ?>
    <input type="hidden" name="slug" value="<?= htmlspecialchars($editing['slug']) ?>">
    <?php endif; ?>

    <label>Label
        <input type="text" name="label" value="<?= htmlspecialchars($editing['label'] ?? '') ?>" placeholder="e.g. By Membership Type" required>
    </label>

    <label>Group by (optional)
        <select name="group_by">
            <option value="">None — just a saved column layout</option>
            <?php foreach ($allFields as $f): ?>
            <option value="<?= (int) $f['id'] ?>" <?= ($editing['group_by'] ?? null) === $f['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($f['label']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </label>

    <fieldset class="checkbox-group">
        <legend>Columns to show, in order (leave every slot as "—" to show all, in the form's own order)</legend>
        <?php for ($i = 1; $i <= max(count($allFields), 1); $i++): ?>
        <label>Column <?= $i ?>
            <select name="view_col<?= $i ?>">
                <option value="">—</option>
                <?php foreach ($allFields as $f): ?>
                <option value="<?= (int) $f['id'] ?>" <?= ($editingColumns[$i - 1] ?? null) === $f['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($f['label']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </label>
        <?php endfor; ?>
        <?php if (empty($allFields)): ?>
        <span class="empty-note">Couldn't load this form's fields — check the database connection.</span>
        <?php endif; ?>
    </fieldset>

    <div class="filter-actions">
        <button type="submit"><?= $editing !== null ? 'Save changes' : 'Add view' ?></button>
        <?php if ($editing !== null): ?>
        <a href="manage.php?form=<?= (int) $formId ?>">Cancel</a>
        <?php endif; ?>
    </div>
</form>
