<?php

declare(strict_types=1);

final class FormRepository
{
    public function __construct(private PDO $pdo, private array $tables)
    {
    }

    public function getForm(int $formId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT id, title FROM {$this->tables['form']} WHERE id = ?");
        $stmt->execute([$formId]);
        $form = $stmt->fetch();

        return $form ?: null;
    }

    /**
     * Returns the form's fields in display order, e.g.:
     * [ ['id' => 5, 'label' => 'Group Name', 'type' => 'text', 'inputs' => []], ... ]
     */
    public function getFields(int $formId): array
    {
        $stmt = $this->pdo->prepare("SELECT display_meta FROM {$this->tables['form_meta']} WHERE form_id = ?");
        $stmt->execute([$formId]);
        $raw = $stmt->fetchColumn();

        if ($raw === false) {
            return [];
        }

        $meta = json_decode((string) $raw, true);
        $fields = [];

        foreach ($meta['fields'] ?? [] as $field) {
            if (empty($field['id'])) {
                continue;
            }

            $inputs = [];
            foreach ($field['inputs'] ?? [] as $input) {
                if (isset($input['id'])) {
                    $inputs[] = [
                        'id'    => (string) $input['id'],
                        'label' => $input['label'] ?? '',
                    ];
                }
            }

            $fields[] = [
                'id'     => (int) $field['id'],
                'label'  => $field['label'] ?? ('Field ' . $field['id']),
                'type'   => $field['type'] ?? 'text',
                'inputs' => $inputs,
            ];
        }

        return $fields;
    }

    public function getFieldLabel(array $fields, int $fieldId): string
    {
        foreach ($fields as $field) {
            if ($field['id'] === $fieldId) {
                return $field['label'];
            }
        }

        return "Field {$fieldId}";
    }
}
