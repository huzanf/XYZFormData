<?php

declare(strict_types=1);

final class EntryRepository
{
    public function __construct(private PDO $pdo, private array $tables)
    {
    }

    /**
     * Entry IDs for a form matching every given filter (AND across
     * filters), ordered newest first. Each filter narrows the set down via
     * its own SELECT; results are intersected in PHP rather than built as
     * one large dynamic JOIN, which keeps each filter's SQL simple and
     * correct regardless of how many are combined.
     *
     * $paymentConfig (from ConfigStore::paymentConfig(), or null) and
     * $hiddenIds (from EntryOverrideStore::hiddenIdsForForm()) are applied
     * last, after the caller's own filters, so every entry point (Full
     * Data, quick views, Sheets, export) sees the same visibility rules
     * without each having to re-implement them.
     *
     * @param int[] $hiddenIds
     * @return int[]
     */
    public function matchingEntryIds(int $formId, array $filters, ?array $paymentConfig = null, array $hiddenIds = []): array
    {
        $ids = array_column($this->baseEntries($formId), 'id');
        $ids = array_map('intval', $ids);

        foreach ($filters as $filter) {
            $matches = $this->idsForFilter($formId, $filter);
            $ids = array_values(array_intersect($ids, $matches));
        }

        if ($paymentConfig !== null && !empty($paymentConfig['enabled'])) {
            $ids = $this->applyPaymentVisibility($ids, $paymentConfig);
        }

        if (!empty($hiddenIds)) {
            $ids = array_values(array_diff($ids, $hiddenIds));
        }

        return $ids;
    }

    /**
     * Drops online-payment entries that haven't actually succeeded yet
     * (Gravity Forms keeps the entry even when checkout was abandoned).
     * An entry is only ever excluded here if its configured "mode" field
     * says it's an online payment — offline/cash entries are never
     * filtered by this, regardless of GF's payment_status for them.
     *
     * @param int[] $ids
     * @return int[]
     */
    private function applyPaymentVisibility(array $ids, array $paymentConfig): array
    {
        if (empty($ids)) {
            return $ids;
        }

        $modeValues = $this->getFieldValuesForIds($ids, (int) $paymentConfig['mode_field']);
        $paymentInfo = $this->fetchPaymentInfo($ids);
        $offlineValue = (string) $paymentConfig['offline_value'];
        $successStatuses = $paymentConfig['success_statuses'] ?? ['Paid'];

        return array_values(array_filter($ids, function (int $id) use ($modeValues, $paymentInfo, $offlineValue, $successStatuses) {
            if (($modeValues[$id] ?? '') === $offlineValue) {
                return true; // offline/cash entries are always visible here
            }

            $info = $paymentInfo[$id] ?? ['payment_status' => '', 'transaction_id' => ''];

            return $info['transaction_id'] !== '' && in_array($info['payment_status'], $successStatuses, true);
        }));
    }

    /**
     * A single field's first value per entry (unlike getValuesForEntries,
     * scoped to one field and returned as a plain string, for cheap
     * per-entry lookups like the payment-mode field).
     *
     * @param int[] $ids
     * @return array<int, string>
     */
    private function getFieldValuesForIds(array $ids, int $fieldId): array
    {
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT entry_id, meta_value FROM {$this->tables['entry_meta']}
             WHERE entry_id IN ({$placeholders}) AND (meta_key = ? OR meta_key LIKE ?)"
        );
        $stmt->execute([...$ids, (string) $fieldId, $fieldId . '.%']);

        $values = [];
        foreach ($stmt->fetchAll() as $row) {
            $entryId = (int) $row['entry_id'];
            if (!isset($values[$entryId])) {
                $values[$entryId] = (string) $row['meta_value'];
            }
        }

        return $values;
    }

    /**
     * Gravity Forms' own core payment columns (not entry_meta) for a batch
     * of entries.
     *
     * @param int[] $ids
     * @return array<int, array{payment_status:string, transaction_id:string}>
     */
    private function fetchPaymentInfo(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT id, payment_status, transaction_id FROM {$this->tables['entry']} WHERE id IN ({$placeholders})"
        );
        $stmt->execute($ids);

        $info = [];
        foreach ($stmt->fetchAll() as $row) {
            $info[(int) $row['id']] = [
                'payment_status'  => (string) ($row['payment_status'] ?? ''),
                'transaction_id'  => (string) ($row['transaction_id'] ?? ''),
            ];
        }

        return $info;
    }

    /**
     * @param int[] $ids ordered entry IDs (e.g. from matchingEntryIds)
     * @return array{total:int, entries:array}
     */
    public function search(int $formId, array $filters, int $limit, int $offset, ?array $paymentConfig = null, array $hiddenIds = []): array
    {
        $ids = $this->matchingEntryIds($formId, $filters, $paymentConfig, $hiddenIds);
        $pageIds = array_slice($ids, $offset, $limit);

        return [
            'total'   => count($ids),
            'entries' => $this->fetchEntriesByIds($pageIds),
        ];
    }

    /**
     * @param int[] $ids
     * @return array ordered the same as $ids, each row has id/date_created/status
     */
    public function fetchEntriesByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT id, date_created, status, payment_status, transaction_id
             FROM {$this->tables['entry']} WHERE id IN ({$placeholders})"
        );
        $stmt->execute($ids);

        $byId = [];
        foreach ($stmt->fetchAll() as $row) {
            $byId[(int) $row['id']] = $row;
        }

        $ordered = [];
        foreach ($ids as $id) {
            if (isset($byId[$id])) {
                $ordered[] = $byId[$id];
            }
        }

        return $ordered;
    }

    /**
     * Loads field values for a batch of entries in one query, grouping
     * sub-inputs (e.g. checkboxes, addresses, names) under their parent
     * field id.
     *
     * @param int[] $entryIds
     * @return array<int, array<int, string[]>> entry_id => field_id => values
     */
    public function getValuesForEntries(array $entryIds): array
    {
        if (empty($entryIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($entryIds), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT entry_id, meta_key, meta_value FROM {$this->tables['entry_meta']}
             WHERE entry_id IN ({$placeholders})"
        );
        $stmt->execute($entryIds);

        $values = [];
        while ($row = $stmt->fetch()) {
            $fieldPart = explode('.', (string) $row['meta_key'])[0];
            if (!is_numeric($fieldPart)) {
                continue; // skip internal Gravity Forms meta keys (e.g. is_fulfilled)
            }

            if ($row['meta_value'] === null || $row['meta_value'] === '') {
                continue;
            }

            $values[(int) $row['entry_id']][(int) $fieldPart][] = (string) $row['meta_value'];
        }

        return $values;
    }

    /**
     * Distinct values actually present for a field (or its checkbox
     * sub-inputs), with entry counts. Used to populate the filter panel's
     * dropdown/checkbox options from real data rather than the form's
     * configured choices, so options always match what's filterable.
     */
    public function getDistinctValues(int $formId, int $fieldId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT em.meta_value AS value, COUNT(DISTINCT em.entry_id) AS total
             FROM {$this->tables['entry_meta']} em
             WHERE em.form_id = ?
               AND (em.meta_key = ? OR em.meta_key LIKE ?)
               AND em.meta_value IS NOT NULL AND em.meta_value <> ''
             GROUP BY em.meta_value
             ORDER BY em.meta_value ASC"
        );
        $stmt->execute([$formId, (string) $fieldId, $fieldId . '.%']);

        return $stmt->fetchAll();
    }

    private function baseEntries(int $formId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, date_created FROM {$this->tables['entry']}
             WHERE form_id = ? AND status = 'active'
             ORDER BY date_created DESC"
        );
        $stmt->execute([$formId]);

        return $stmt->fetchAll();
    }

    /**
     * @return int[]
     */
    private function idsForFilter(int $formId, array $filter): array
    {
        return match ($filter['kind']) {
            'date_created' => $this->idsByDateRange($formId, $filter['from'], $filter['to']),
            'text'         => $this->idsByTextContains($formId, $filter['field_id'], $filter['value']),
            'choice'       => $this->idsByExactValue($formId, $filter['field_id'], $filter['value']),
            'multi'        => $this->idsByAnyValue($formId, $filter['field_id'], $filter['values']),
            'range'        => $this->idsByNumericRange($formId, $filter['field_id'], $filter['min'], $filter['max']),
            default        => [],
        };
    }

    private function idsByDateRange(int $formId, ?string $from, ?string $to): array
    {
        $sql = "SELECT id FROM {$this->tables['entry']} WHERE form_id = ? AND status = 'active'";
        $params = [$formId];

        if ($from !== null) {
            $sql .= ' AND date_created >= ?';
            $params[] = $from . ' 00:00:00';
        }
        if ($to !== null) {
            $sql .= ' AND date_created <= ?';
            $params[] = $to . ' 23:59:59';
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return array_map('intval', array_column($stmt->fetchAll(), 'id'));
    }

    private function idsByTextContains(int $formId, int $fieldId, string $needle): array
    {
        $escaped = addcslashes($needle, '%_\\');
        $stmt = $this->pdo->prepare(
            "SELECT DISTINCT em.entry_id AS id FROM {$this->tables['entry_meta']} em
             WHERE em.form_id = ? AND (em.meta_key = ? OR em.meta_key LIKE ?)
               AND em.meta_value LIKE ? ESCAPE '\\'"
        );
        $stmt->execute([$formId, (string) $fieldId, $fieldId . '.%', '%' . $escaped . '%']);

        return array_map('intval', array_column($stmt->fetchAll(), 'id'));
    }

    private function idsByExactValue(int $formId, int $fieldId, string $value): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT DISTINCT em.entry_id AS id FROM {$this->tables['entry_meta']} em
             WHERE em.form_id = ? AND (em.meta_key = ? OR em.meta_key LIKE ?) AND em.meta_value = ?"
        );
        $stmt->execute([$formId, (string) $fieldId, $fieldId . '.%', $value]);

        return array_map('intval', array_column($stmt->fetchAll(), 'id'));
    }

    private function idsByAnyValue(int $formId, int $fieldId, array $values): array
    {
        if (empty($values)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT DISTINCT em.entry_id AS id FROM {$this->tables['entry_meta']} em
             WHERE em.form_id = ? AND (em.meta_key = ? OR em.meta_key LIKE ?)
               AND em.meta_value IN ({$placeholders})"
        );
        $stmt->execute([$formId, (string) $fieldId, $fieldId . '.%', ...$values]);

        return array_map('intval', array_column($stmt->fetchAll(), 'id'));
    }

    private function idsByNumericRange(int $formId, int $fieldId, ?float $min, ?float $max): array
    {
        $expr = "CAST(REPLACE(REPLACE(em.meta_value, '$', ''), ',', '') AS DECIMAL(18,2))";
        $conditions = ['em.form_id = ?', 'em.meta_key = ?'];
        $params = [$formId, (string) $fieldId];

        if ($min !== null) {
            $conditions[] = "{$expr} >= ?";
            $params[] = $min;
        }
        if ($max !== null) {
            $conditions[] = "{$expr} <= ?";
            $params[] = $max;
        }

        $sql = "SELECT DISTINCT em.entry_id AS id FROM {$this->tables['entry_meta']} em WHERE " . implode(' AND ', $conditions);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return array_map('intval', array_column($stmt->fetchAll(), 'id'));
    }
}
