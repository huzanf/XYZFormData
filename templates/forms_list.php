<h1>Forms</h1>
<?php if (Auth::isAdmin()): ?>
<p class="meta"><a href="manage.php">Manage forms &amp; views &rarr;</a> &middot; <a href="users.php">Manage users &rarr;</a></p>
<?php endif; ?>
<div class="card-grid">
<?php foreach ($formsConfig as $formId => $formDef): ?>
    <div class="card">
        <h2><?= htmlspecialchars($formDef['label']) ?></h2>
        <?php if (!empty($formDef['date_created'])): ?>
        <p class="meta">Created <?= htmlspecialchars(substr((string) $formDef['date_created'], 0, 10)) ?></p>
        <?php else: ?>
        <p class="meta warning">Form ID <?= (int) $formId ?> not found in the database &mdash; check it under <a href="manage.php">Manage forms</a></p>
        <?php endif; ?>
        <a href="view.php?form=<?= (int) $formId ?>">View data &amp; filters &rarr;</a>
    </div>
<?php endforeach; ?>
<?php if (empty($formsConfig)): ?>
<p class="meta">No forms configured yet &mdash; add one under <a href="manage.php">Manage forms</a>.</p>
<?php endif; ?>
</div>
