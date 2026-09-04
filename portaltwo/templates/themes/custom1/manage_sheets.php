<h2 id="sheets">Sheets</h2>
<p class="meta">Derived, pivoted breakdowns (group-by columns, per-category sections, presence columns) shown on the Sheets tab and its Excel export — <a href="sheets.php?form=<?= (int) $formId ?>">preview &rarr;</a></p>

<div class="table-wrap">
<table>
<thead><tr><th>Label</th><th>Type</th><th>Summary</th><th></th></tr></thead>
<tbody>
<?php foreach ($sheets as $sheet):
    $fieldLabel = static function ($id) use ($allFields) {
        foreach ($allFields as $f) {
            if ($f['id'] === (int) $id) {
                return $f['label'];
            }
        }
        return "Field {$id}";
    };

    $typeLabels = [
        'complete'         => 'Complete Table',
        'group_columns'    => 'Group Sheet',
        'value_sections'   => 'Member Category',
        'presence_columns' => 'Event List',
    ];

    $summary = match ($sheet['type']) {
        'complete'         => empty($sheet['columns']) ? 'All columns' : count($sheet['columns']) . ' columns',
        'group_columns'    => 'Group by: ' . $fieldLabel($sheet['group_by'] ?? 0),
        'value_sections'   => 'Category: ' . $fieldLabel($sheet['category_by'] ?? 0),
        'presence_columns' => count($sheet['fields'] ?? []) . ' event fields',
        default            => '',
    };
?>
<tr>
    <td><?= htmlspecialchars($sheet['label']) ?></td>
    <td><?= htmlspecialchars($typeLabels[$sheet['type']] ?? $sheet['type']) ?></td>
    <td><?= htmlspecialchars($summary) ?></td>
    <td>
        <a href="manage.php?form=<?= (int) $formId ?>&edit_sheet=<?= urlencode($sheet['slug']) ?>#sheets">Edit</a>
        &middot;
        <form method="post" class="inline" onsubmit="return confirm('Delete this sheet?');">
            <input type="hidden" name="action" value="delete_sheet">
            <input type="hidden" name="form_id" value="<?= (int) $formId ?>">
            <input type="hidden" name="slug" value="<?= htmlspecialchars($sheet['slug']) ?>">
            <button type="submit" class="link-button danger">Delete</button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
<?php if (empty($sheets)): ?>
<tr><td colspan="4">No sheets configured yet — the Sheets tab won't appear for this form until you add one.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>

<?php
$editingSheetSlug = $_GET['edit_sheet'] ?? null;
$editingSheet = null;
foreach ($sheets as $sheet) {
    if ($sheet['slug'] === $editingSheetSlug) {
        $editingSheet = $sheet;
        break;
    }
}
$editingSheetType = $editingSheet['type'] ?? 'complete';
$editingSheetColumns = $editingSheet['columns'] ?? [];
$editingSheetFields = $editingSheet['fields'] ?? [];
?>

<h2><?= $editingSheet !== null ? 'Edit sheet' : 'Add a sheet' ?></h2>
<form method="post" class="manage-view-form">
    <input type="hidden" name="action" value="save_sheet">
    <input type="hidden" name="form_id" value="<?= (int) $formId ?>">
    <?php if ($editingSheet !== null): ?>
    <input type="hidden" name="sheet_slug" value="<?= htmlspecialchars($editingSheet['slug']) ?>">
    <?php endif; ?>

    <label>Label
        <input type="text" name="sheet_label" value="<?= htmlspecialchars($editingSheet['label'] ?? '') ?>" placeholder="e.g. Group Sheet" required>
    </label>

    <label>Type
        <select name="sheet_type" id="sheetType">
            <option value="complete" <?= $editingSheetType === 'complete' ? 'selected' : '' ?>>Complete Table — the raw data</option>
            <option value="group_columns" <?= $editingSheetType === 'group_columns' ? 'selected' : '' ?>>Group Sheet — side-by-side block per group value</option>
            <option value="value_sections" <?= $editingSheetType === 'value_sections' ? 'selected' : '' ?>>Member Category — stacked table per category value</option>
            <option value="presence_columns" <?= $editingSheetType === 'presence_columns' ? 'selected' : '' ?>>Event List — side-by-side block per event field</option>
        </select>
    </label>

    <div class="sheet-type-fields" data-type="complete">
        <fieldset class="checkbox-group">
            <legend>Columns to show (none checked = show all)</legend>
            <?php foreach ($allFields as $f): ?>
            <label class="checkbox-option">
                <input type="checkbox" name="complete_columns[]" value="<?= (int) $f['id'] ?>"
                    <?= in_array($f['id'], $editingSheetColumns, true) ? 'checked' : '' ?>>
                <?= htmlspecialchars($f['label']) ?>
            </label>
            <?php endforeach; ?>
        </fieldset>
    </div>

    <div class="sheet-type-fields" data-type="group_columns">
        <label>Group by
            <select name="group_by">
                <option value="">— choose a field —</option>
                <?php foreach ($allFields as $f): ?>
                <option value="<?= (int) $f['id'] ?>" <?= ($editingSheet['group_by'] ?? null) === $f['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($f['label']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </label>
        <p class="empty-note">Column 1 is shown under each group's own value as its header (e.g. a "DD" column holding member names) — put the field that identifies each row (usually Name) first.</p>
        <?php for ($i = 1; $i <= 6; $i++): ?>
        <label>Column <?= $i ?><?= $i === 1 ? ' (e.g. Name)' : '' ?>
            <select name="gc_col<?= $i ?>">
                <option value="">—</option>
                <?php foreach ($allFields as $f): ?>
                <option value="<?= (int) $f['id'] ?>" <?= ($editingSheetColumns[$i - 1] ?? null) === $f['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($f['label']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </label>
        <?php endfor; ?>
    </div>

    <div class="sheet-type-fields" data-type="value_sections">
        <label>Category field
            <select name="category_by">
                <option value="">— choose a field —</option>
                <?php foreach ($allFields as $f): ?>
                <option value="<?= (int) $f['id'] ?>" <?= ($editingSheet['category_by'] ?? null) === $f['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($f['label']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </label>
        <p class="empty-note">A choice-type field's configured options each get their own section, even with zero matching entries (e.g. an empty "Parent" table) — a plain text field only shows values that actually appear in the data.</p>
        <?php for ($i = 1; $i <= 6; $i++): ?>
        <label>Column <?= $i ?>
            <select name="vs_col<?= $i ?>">
                <option value="">—</option>
                <?php foreach ($allFields as $f): ?>
                <option value="<?= (int) $f['id'] ?>" <?= ($editingSheetColumns[$i - 1] ?? null) === $f['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($f['label']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </label>
        <?php endfor; ?>
    </div>

    <div class="sheet-type-fields" data-type="presence_columns">
        <p class="empty-note">One block per event field below, in order — every entry where that field is non-blank gets a row.</p>
        <?php for ($i = 1; $i <= 6; $i++): ?>
        <label>Event field <?= $i ?>
            <select name="pc_field<?= $i ?>">
                <option value="">—</option>
                <?php foreach ($allFields as $f): ?>
                <option value="<?= (int) $f['id'] ?>" <?= ($editingSheetFields[$i - 1] ?? null) === $f['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($f['label']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </label>
        <?php endfor; ?>
        <p class="empty-note">Column 1 is shown under each event field's own label as its header (usually Name) — the rest keep their normal labels (e.g. Group).</p>
        <?php for ($i = 1; $i <= 6; $i++): ?>
        <label>Column <?= $i ?><?= $i === 1 ? ' (e.g. Name)' : '' ?>
            <select name="pc_col<?= $i ?>">
                <option value="">—</option>
                <?php foreach ($allFields as $f): ?>
                <option value="<?= (int) $f['id'] ?>" <?= ($editingSheetColumns[$i - 1] ?? null) === $f['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($f['label']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </label>
        <?php endfor; ?>
    </div>

    <div class="filter-actions">
        <button type="submit"><?= $editingSheet !== null ? 'Save changes' : 'Add sheet' ?></button>
        <?php if ($editingSheet !== null): ?>
        <a href="manage.php?form=<?= (int) $formId ?>#sheets">Cancel</a>
        <?php endif; ?>
    </div>
</form>

<script>
(function () {
    var select = document.getElementById('sheetType');
    var groups = document.querySelectorAll('.sheet-type-fields');

    function sync() {
        groups.forEach(function (el) {
            el.hidden = el.dataset.type !== select.value;
        });
    }

    select.addEventListener('change', sync);
    sync();
})();
</script>
