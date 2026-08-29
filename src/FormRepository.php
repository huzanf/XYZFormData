<?php

declare(strict_types=1);

final class FormRepository
{
    public function __construct(private PDO $pdo, private array $tables)
    {
    }

    public function getForm(int $formId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT id, title, date_created FROM {$this->tables['form']} WHERE id = ?");
        $stmt->execute([$formId]);
        $form = $stmt->fetch();

        return $form ?: null;
    }

    /**
     * Returns the form's fields in display order, each annotated with a
     * 'filter_type' used to decide what filter control the portal shows
     * for it:
     *   - 'choice': single-select dropdown (Gravity Forms select/radio fields)
     *   - 'multi':  multi-select checkboxes (Gravity Forms checkbox fields)
     *   - 'range':  numeric min/max (Gravity Forms number fields)
     *   - 'text':   "contains" search (everything else — text, name,
     *               address, email, textarea, etc.)
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

            $type = $field['type'] ?? 'text';

            $fields[] = [
                'id'          => (int) $field['id'],
                'label'       => $field['label'] ?? ('Field ' . $field['id']),
                'type'        => $type,
                'inputs'      => $inputs,
                'filter_type' => self::classifyFilterType($type),
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

    private static function classifyFilterType(string $type): string
    {
        return match ($type) {
            'select', 'radio' => 'choice',
            'checkbox' => 'multi',
            'number' => 'range',
            default => 'text',
        };
    }
}
