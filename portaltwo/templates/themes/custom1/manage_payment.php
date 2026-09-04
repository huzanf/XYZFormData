<h2 id="payment">Payment</h2>
<p class="meta">Gravity Forms keeps an entry even when an online payment was never completed. When enabled, only online entries with a successful, transaction-id'd payment are shown by default — offline/cash entries are always shown, with a "Mark paid" toggle on the data table.</p>

<form method="post" class="manage-view-form">
    <input type="hidden" name="action" value="save_payment">
    <input type="hidden" name="form_id" value="<?= (int) $formId ?>">

    <label>
        <input type="checkbox" name="enabled" value="1" id="paymentEnabled" <?= !empty($paymentConfig['enabled']) ? 'checked' : '' ?>>
        This form has an online/offline payment field
    </label>

    <div id="paymentFields" <?= empty($paymentConfig['enabled']) ? 'hidden' : '' ?>>
        <label>Payment mode field
            <select name="mode_field">
                <option value="">— choose the field that says Online/Offline —</option>
                <?php foreach ($allFields as $f): ?>
                <option value="<?= (int) $f['id'] ?>" <?= ($paymentConfig['mode_field'] ?? null) === $f['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($f['label']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>Offline/cash value
            <input type="text" name="offline_value" value="<?= htmlspecialchars($paymentConfig['offline_value'] ?? '') ?>" placeholder="e.g. Cash">
        </label>
        <p class="empty-note">The exact value the mode field holds for an offline/cash entry (matched exactly). Anything else in that field is treated as an online payment.</p>

        <label>Successful payment status value(s)
            <input type="text" name="success_statuses" value="<?= htmlspecialchars(implode(', ', $paymentConfig['success_statuses'] ?? ['Paid'])) ?>" placeholder="Paid">
        </label>
        <p class="empty-note">Comma-separated if your gateway can report more than one "it went through" status. Gravity Forms' own default is "Paid" — check your wp_gf_entry table's payment_status column if you're not sure what yours uses.</p>
    </div>

    <div class="filter-actions">
        <button type="submit">Save payment settings</button>
    </div>
</form>

<script>
(function () {
    var checkbox = document.getElementById('paymentEnabled');
    var fields = document.getElementById('paymentFields');
    checkbox.addEventListener('change', function () {
        fields.hidden = !checkbox.checked;
    });
})();
</script>
