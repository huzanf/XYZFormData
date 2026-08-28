<h1>Forms</h1>
<div class="card-grid">
<?php foreach ($formsConfig as $formId => $formDef): ?>
    <div class="card">
        <h2><?= htmlspecialchars($formDef['label']) ?></h2>
        <ul class="view-links">
        <?php foreach ($formDef['views'] as $viewSlug => $viewDef): ?>
            <li>
                <a href="view.php?form=<?= (int) $formId ?>&view=<?= urlencode($viewSlug) ?>">
                    <?= htmlspecialchars($viewDef['label']) ?>
                </a>
            </li>
        <?php endforeach; ?>
        </ul>
    </div>
<?php endforeach; ?>
</div>
