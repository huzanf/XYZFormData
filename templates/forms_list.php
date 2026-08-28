<h1>Forms</h1>
<div class="card-grid">
<?php foreach ($formsConfig as $formId => $formDef): ?>
    <div class="card">
        <h2><?= htmlspecialchars($formDef['label']) ?></h2>
        <a href="view.php?form=<?= (int) $formId ?>">View data &amp; filters &rarr;</a>
    </div>
<?php endforeach; ?>
</div>
