<h1>Manage Users</h1>
<p class="meta"><a href="index.php">&larr; Back to forms</a></p>

<?php if ($flash !== null): ?>
<p class="flash <?= $flash['type'] === 'ok' ? 'flash-ok' : 'flash-error' ?>"><?= htmlspecialchars($flash['message']) ?></p>
<?php endif; ?>

<h2>Add or update a user</h2>
<p class="meta">Sign-in is email + a one-time code &mdash; there's no password to set. Saving an email that already exists updates that user's name/role instead of creating a duplicate.</p>
<form method="post" class="inline-form">
    <input type="hidden" name="action" value="save">
    <label>Full name
        <input type="text" name="name" required>
    </label>
    <label>Email
        <input type="email" name="email" required>
    </label>
    <label>Role
        <select name="role">
            <option value="viewer">viewer (view &amp; export only)</option>
            <option value="admin">admin (full access)</option>
        </select>
    </label>
    <button type="submit">Save user</button>
</form>

<h2>Existing users</h2>
<div class="table-wrap">
<table>
<thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last sign-in</th><th></th></tr></thead>
<tbody>
<?php foreach ($users as $u): ?>
<tr>
    <td><?= htmlspecialchars($u['name']) ?></td>
    <td><?= htmlspecialchars($u['email']) ?></td>
    <td><?= htmlspecialchars($u['role']) ?></td>
    <td><?= $u['is_active'] ? 'Active' : '<span class="meta warning">Inactive</span>' ?></td>
    <td><?= $u['last_login_at'] ? htmlspecialchars($u['last_login_at']) : '—' ?></td>
    <td>
        <?php if ((int) $u['id'] !== (int) ($_SESSION['user_id'] ?? 0)): ?>
        <form method="post" class="inline" onsubmit="return confirm('<?= $u['is_active'] ? 'Deactivate' : 'Reactivate' ?> <?= htmlspecialchars($u['name']) ?>?');">
            <input type="hidden" name="action" value="toggle_active">
            <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
            <button type="submit" class="link-button <?= $u['is_active'] ? 'danger' : '' ?>"><?= $u['is_active'] ? 'Deactivate' : 'Reactivate' ?></button>
        </form>
        <?php else: ?>
        <span class="meta">(you)</span>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
<?php if (empty($users)): ?>
<tr><td colspan="6">No users yet.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
