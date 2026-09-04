<nav class="view-tabs">
    <a href="view.php?form=<?= (int) $formId ?>" class="<?= (!$onSheetsPage && $qvSlug === null) ? 'active' : '' ?>">Full Data</a>
    <?php foreach ($quickViews as $qv): ?>
    <a href="view.php?form=<?= (int) $formId ?>&qv=<?= urlencode($qv['slug']) ?>"
       class="<?= (!$onSheetsPage && $qvSlug === $qv['slug']) ? 'active' : '' ?>"><?= htmlspecialchars($qv['label']) ?></a>
    <?php endforeach; ?>
    <?php if ($hasSheets): ?>
    <a href="sheets.php?form=<?= (int) $formId ?>" class="<?= $onSheetsPage ? 'active' : '' ?>">Sheets</a>
    <?php endif; ?>
    <a href="manage.php?form=<?= (int) $formId ?>" class="manage-link">Manage views &rarr;</a>
</nav>
