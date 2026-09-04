<?php

declare(strict_types=1);

/**
 * Portal-only, per-entry state that never touches the WordPress database:
 * hiding a (likely duplicate) entry from every view/export, and marking an
 * offline/cash entry as paid. Backed by the portal_db's entry_overrides
 * table (see portal_schema.sql) — entirely separate from the read-only
 * WordPress connection Gravity Forms data comes from.
 */
final class EntryOverrideStore
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @return int[] entry IDs currently hidden for this form
     */
    public function hiddenIdsForForm(int $formId): array
    {
        $stmt = $this->pdo->prepare('SELECT entry_id FROM entry_overrides WHERE form_id = ? AND hidden = 1');
        $stmt->execute([$formId]);

        return array_map('intval', array_column($stmt->fetchAll(), 'entry_id'));
    }

    /**
     * @param int[] $entryIds
     * @return array<int, bool> entry_id => marked_paid, only for ids that have a row
     */
    public function paidMap(int $formId, array $entryIds): array
    {
        if (empty($entryIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($entryIds), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT entry_id, marked_paid FROM entry_overrides WHERE form_id = ? AND entry_id IN ({$placeholders})"
        );
        $stmt->execute([$formId, ...$entryIds]);

        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $map[(int) $row['entry_id']] = (bool) $row['marked_paid'];
        }

        return $map;
    }

    public function setHidden(int $formId, int $entryId, bool $hidden, int $byUserId): void
    {
        $this->upsert($formId, $entryId, [
            'hidden'    => $hidden ? 1 : 0,
            'hidden_by' => $hidden ? $byUserId : null,
            'hidden_at' => $hidden ? date('Y-m-d H:i:s') : null,
        ]);
    }

    public function setPaid(int $formId, int $entryId, bool $paid, int $byUserId): void
    {
        $this->upsert($formId, $entryId, [
            'marked_paid'    => $paid ? 1 : 0,
            'marked_paid_by' => $paid ? $byUserId : null,
            'marked_paid_at' => $paid ? date('Y-m-d H:i:s') : null,
        ]);
    }

    /**
     * All non-default override rows for a form (hidden or marked paid),
     * for the admin "Hidden & Paid entries" screen.
     */
    public function allForForm(int $formId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM entry_overrides WHERE form_id = ? AND (hidden = 1 OR marked_paid = 1) ORDER BY entry_id DESC'
        );
        $stmt->execute([$formId]);

        return $stmt->fetchAll();
    }

    private function upsert(int $formId, int $entryId, array $fields): void
    {
        $existing = $this->pdo->prepare('SELECT 1 FROM entry_overrides WHERE form_id = ? AND entry_id = ?');
        $existing->execute([$formId, $entryId]);

        if ($existing->fetchColumn() !== false) {
            $set = implode(', ', array_map(fn ($col) => "{$col} = ?", array_keys($fields)));
            $this->pdo->prepare("UPDATE entry_overrides SET {$set} WHERE form_id = ? AND entry_id = ?")
                ->execute([...array_values($fields), $formId, $entryId]);

            return;
        }

        $columns = array_merge(['form_id', 'entry_id'], array_keys($fields));
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $this->pdo->prepare('INSERT INTO entry_overrides (' . implode(', ', $columns) . ") VALUES ({$placeholders})")
            ->execute([$formId, $entryId, ...array_values($fields)]);
    }
}
