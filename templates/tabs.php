<nav class="view-tabs">
    <a href="view.php?form=<?= (int) $formId ?>" class="<?= $qvSlug === null ? 'active' : '' ?>">Full Data</a>
    <?php foreach ($quickViews as $qv): ?>
    <a href="view.php?form=<?= (int) $formId ?>&qv=<?= urlencode($qv['slug']) ?>"
       class="<?= $qvSlug === $qv['slug'] ? 'active' : '' ?>"><?= htmlspecialchars($qv['label']) ?></a>
    <?php endforeach; ?>
    <a href="manage.php?form=<?= (int) $formId ?>" class="manage-link">Manage views &rarr;</a>
</nav>
