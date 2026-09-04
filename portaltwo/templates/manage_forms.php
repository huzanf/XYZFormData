<h1>Manage Forms</h1>
<p class="meta"><a href="index.php">&larr; Back to forms</a></p>

<div class="table-wrap">
<table>
<thead><tr><th>Form ID</th><th>Label</th><th>Views</th><th></th></tr></thead>
<tbody>
<?php foreach ($forms as $formId => $def): ?>
<tr>
    <td><?= (int) $formId ?></td>
    <td><?= htmlspecialchars($def['label']) ?></td>
    <td><?= count($def['views'] ?? []) ?></td>
    <td>
        <a href="manage.php?form=<?= (int) $formId ?>">Manage views</a>
        &middot;
        <form method="post" class="inline" onsubmit="return confirm('Remove this form from the portal? Its data stays in WordPress — this only removes it from here.');">
            <input type="hidden" name="action" value="delete_form">
            <input type="hidden" name="form_id" value="<?= (int) $formId ?>">
            <button type="submit" class="link-button danger">Remove</button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
<?php if (empty($forms)): ?>
<tr><td colspan="4">No forms configured yet.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>

<h2>Add a form</h2>
<form method="post" class="inline-form">
    <input type="hidden" name="action" value="save_form">
    <label>Gravity Forms Form ID
        <input type="number" name="form_id" min="1" required>
    </label>
    <label>Label
        <input type="text" name="label" placeholder="e.g. Yearly Membership Form" required>
    </label>
    <button type="submit">Add</button>
</form>
<p class="meta">Find the form ID in the WP admin URL when editing the form, or the "id" column in wp_gf_form.</p>
