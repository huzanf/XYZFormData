<form method="get" action="view.php" class="filter-panel">
    <input type="hidden" name="form" value="<?= (int) $formId ?>">

    <div class="filter-row">
        <label>Date from
            <input type="date" name="date_from" value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>">
        </label>
        <label>Date to
            <input type="date" name="date_to" value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>">
        </label>
    </div>

    <?php foreach ($filterableFields as $field): ?>
    <div class="filter-row">
    <?php if ($field['filter_type'] === 'multi'): ?>
        <fieldset class="checkbox-group">
            <legend><?= htmlspecialchars($field['label']) ?></legend>
            <?php foreach ($field['distinct'] as $opt): ?>
            <label class="checkbox-option">
                <input type="checkbox" name="f[<?= (int) $field['id'] ?>][]" value="<?= htmlspecialchars($opt['value']) ?>"
                    <?= in_array($opt['value'], $field['selected'], true) ? 'checked' : '' ?>>
                <?= htmlspecialchars($opt['value']) ?> (<?= (int) $opt['total'] ?>)
            </label>
            <?php endforeach; ?>
            <?php if (empty($field['distinct'])): ?>
            <span class="empty-note">No values yet</span>
            <?php endif; ?>
        </fieldset>
    <?php else: ?>
        <label>
            <?= htmlspecialchars($field['label']) ?>
            <?php if ($field['filter_type'] === 'text'): ?>
                <input type="text" name="f[<?= (int) $field['id'] ?>]" value="<?= htmlspecialchars($field['selected']) ?>" placeholder="Contains...">
            <?php elseif ($field['filter_type'] === 'choice'): ?>
                <select name="f[<?= (int) $field['id'] ?>]">
                    <option value="">Any</option>
                    <?php foreach ($field['distinct'] as $opt): ?>
                    <option value="<?= htmlspecialchars($opt['value']) ?>" <?= $field['selected'] === $opt['value'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($opt['value']) ?> (<?= (int) $opt['total'] ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            <?php elseif ($field['filter_type'] === 'range'): ?>
                <span class="range-inputs">
                    <input type="number" step="0.01" name="f[<?= (int) $field['id'] ?>][min]" value="<?= htmlspecialchars($field['selected_min']) ?>" placeholder="Min">
                    &ndash;
                    <input type="number" step="0.01" name="f[<?= (int) $field['id'] ?>][max]" value="<?= htmlspecialchars($field['selected_max']) ?>" placeholder="Max">
                </span>
            <?php endif; ?>
        </label>
    <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <div class="filter-actions">
        <button type="submit">Apply Filters</button>
        <a class="reset-link" href="view.php?form=<?= (int) $formId ?>">Reset</a>
        <button type="submit" formaction="export.php">Export to Excel</button>
    </div>
</form>
