<?php
$onSheetsPage = basename((string) ($_SERVER['SCRIPT_NAME'] ?? '')) === 'sheets.php';
// The including page may already know this (e.g. the demo, whose sheet
// definitions live under demo/ rather than the root app's data/sheets.json)
// — only fall back to the default root-relative store if it doesn't.
if (!isset($hasSheets)) {
    require_once dirname(__DIR__) . '/src/SheetConfigStore.php';
    $sheetStoreForTabs = new SheetConfigStore(dirname(__DIR__) . '/data/sheets.json', dirname(__DIR__) . '/config/sheets.php');
    $hasSheets = !empty($sheetStoreForTabs->sheetsForForm($formId));
}
?>
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
