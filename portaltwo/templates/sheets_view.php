<h1><?= htmlspecialchars($formDef['label']) ?> — Sheets</h1>
<p class="meta">
    <a class="btn-link" href="sheets.php?form=<?= (int) $formId ?>&export=xlsx">Export all sheets (.xlsx)</a>
</p>

<?php foreach ($sheets as $sheet): ?>
<section class="sheet-section">
<h2><?= htmlspecialchars($sheet['label']) ?></h2>

<?php if ($sheet['layout'] === 'flat'): ?>
    <?php $block = $sheet['blocks'][0]; ?>
    <div class="table-wrap">
    <table>
    <thead><tr><?php foreach ($block['headers'] as $h): ?><th><?= htmlspecialchars($h) ?></th><?php endforeach; ?></tr></thead>
    <tbody>
    <?php foreach ($block['rows'] as $row): ?>
    <tr><?php foreach ($row as $cell): ?><td><?= htmlspecialchars($cell) ?></td><?php endforeach; ?></tr>
    <?php endforeach; ?>
    <?php if (empty($block['rows'])): ?>
    <tr><td colspan="<?= count($block['headers']) ?>">No data available.</td></tr>
    <?php endif; ?>
    </tbody>
    </table>
    </div>

<?php else: /* side_by_side */ ?>
    <div class="sheet-columns">
    <?php foreach ($sheet['blocks'] as $block): ?>
    <div class="sheet-column">
    <div class="table-wrap">
    <table>
    <thead><tr><?php foreach ($block['headers'] as $h): ?><th><?= htmlspecialchars($h) ?></th><?php endforeach; ?></tr></thead>
    <tbody>
    <?php foreach ($block['rows'] as $row): ?>
    <tr><?php foreach ($row as $cell): ?><td><?= htmlspecialchars($cell) ?></td><?php endforeach; ?></tr>
    <?php endforeach; ?>
    <?php if (empty($block['rows'])): ?>
    <tr><td colspan="<?= count($block['headers']) ?>">No entries.</td></tr>
    <?php endif; ?>
    </tbody>
    </table>
    </div>
    </div>
    <?php endforeach; ?>
    </div>
<?php endif; ?>
</section>
<?php endforeach; ?>
