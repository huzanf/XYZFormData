<?php

declare(strict_types=1);

final class EntryRepository
{
    public function __construct(private PDO $pdo, private array $tables)
    {
    }

    public function countEntries(int $formId): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM {$this->tables['entry']} WHERE form_id = ? AND status = 'active'"
        );
        $stmt->execute([$formId]);

        return (int) $stmt->fetchColumn();
    }

    public function getEntries(int $formId, int $limit, int $offset): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, date_created, status FROM {$this->tables['entry']}
             WHERE form_id = ? AND status = 'active'
             ORDER BY date_created DESC
             LIMIT ? OFFSET ?"
        );
        $stmt->bindValue(1, $formId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Loads field values for a batch of entries in one query, grouping
     * sub-inputs (e.g. checkboxes, addresses) under their parent field id.
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
     * Distinct values for a field (or its checkbox sub-inputs) with entry
     * counts, used to build the index page of a grouped view.
     */
    public function getGroupCounts(int $formId, int $fieldId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT em.meta_value AS value, COUNT(DISTINCT em.entry_id) AS total
             FROM {$this->tables['entry_meta']} em
             INNER JOIN {$this->tables['entry']} e ON e.id = em.entry_id
             WHERE e.form_id = ? AND e.status = 'active'
               AND (em.meta_key = ? OR em.meta_key LIKE ?)
               AND em.meta_value IS NOT NULL AND em.meta_value <> ''
             GROUP BY em.meta_value
             ORDER BY em.meta_value ASC"
        );
        $stmt->execute([$formId, (string) $fieldId, $fieldId . '.%']);

        return $stmt->fetchAll();
    }

    public function countEntriesForGroupValue(int $formId, int $fieldId, string $value): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(DISTINCT em.entry_id)
             FROM {$this->tables['entry_meta']} em
             INNER JOIN {$this->tables['entry']} e ON e.id = em.entry_id
             WHERE e.form_id = ? AND e.status = 'active'
               AND (em.meta_key = ? OR em.meta_key LIKE ?)
               AND em.meta_value = ?"
        );
        $stmt->execute([$formId, (string) $fieldId, $fieldId . '.%', $value]);

        return (int) $stmt->fetchColumn();
    }

    public function getEntriesForGroupValue(int $formId, int $fieldId, string $value, int $limit, int $offset): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT DISTINCT e.id, e.date_created, e.status
             FROM {$this->tables['entry']} e
             INNER JOIN {$this->tables['entry_meta']} em ON em.entry_id = e.id
             WHERE e.form_id = ? AND e.status = 'active'
               AND (em.meta_key = ? OR em.meta_key LIKE ?)
               AND em.meta_value = ?
             ORDER BY e.date_created DESC
             LIMIT ? OFFSET ?"
        );
        $stmt->bindValue(1, $formId, PDO::PARAM_INT);
        $stmt->bindValue(2, (string) $fieldId);
        $stmt->bindValue(3, $fieldId . '.%');
        $stmt->bindValue(4, $value);
        $stmt->bindValue(5, $limit, PDO::PARAM_INT);
        $stmt->bindValue(6, $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
