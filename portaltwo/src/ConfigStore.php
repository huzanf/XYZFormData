<?php

declare(strict_types=1);

/**
 * Persistent, admin-editable replacement for hand-editing config/forms.php:
 * which forms this portal shows, and each form's named "views" (an
 * optional group-by field for browsing, plus which columns to show and
 * in what order) — the same idea as Gravity Forms' Google Sheets add-on's
 * column-mapping screen, just for this portal.
 *
 * Backed by a JSON file (views.json) rather than a database table, since
 * it's small, human-inspectable, and needs no schema/migration tooling.
 * On first use, if that file doesn't exist yet, it's seeded from the
 * legacy config/forms.php array so an existing deployment's config isn't
 * lost — from then on the JSON file is authoritative and config/forms.php
 * is no longer read.
 */
final class ConfigStore
{
    private array $data;

    public function __construct(private string $path, private string $legacyFormsPath)
    {
        $this->data = $this->load();
    }

    public function forms(): array
    {
        return $this->data['forms'] ?? [];
    }

    public function form(int $formId): ?array
    {
        return $this->data['forms'][(string) $formId] ?? null;
    }

    public function saveForm(int $formId, string $label): void
    {
        $key = (string) $formId;
        $this->data['forms'][$key]['label'] = $label;
        $this->data['forms'][$key]['views'] ??= [];
        $this->persist();
    }

    public function deleteForm(int $formId): void
    {
        unset($this->data['forms'][(string) $formId]);
        $this->persist();
    }

    /**
     * @param array $view ['slug'=>?, 'label'=>string, 'group_by'=>int|null, 'columns'=>int[]|null]
     *                     slug is generated from the label when empty (new view).
     */
    public function saveView(int $formId, array $view): void
    {
        $key = (string) $formId;
        $this->data['forms'][$key]['views'] ??= [];
        $views = $this->data['forms'][$key]['views'];

        if (($view['slug'] ?? '') === '') {
            $existingSlugs = array_column($views, 'slug');
            $view['slug'] = self::slugify($view['label'], $existingSlugs);
        }

        $found = false;
        foreach ($views as $i => $existing) {
            if ($existing['slug'] === $view['slug']) {
                $views[$i] = $view;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $views[] = $view;
        }

        $this->data['forms'][$key]['views'] = $views;
        $this->persist();
    }

    /**
     * A form's payment visibility rule, or null if it doesn't have one
     * (payment filtering is opt-in per form — most forms have no payment
     * field at all and shouldn't be touched).
     *
     * @return array{enabled:bool, mode_field:int, offline_value:string, success_statuses:string[]}|null
     */
    public function paymentConfig(int $formId): ?array
    {
        return $this->data['forms'][(string) $formId]['payment'] ?? null;
    }

    public function savePaymentConfig(int $formId, ?array $config): void
    {
        $key = (string) $formId;
        if ($config === null) {
            unset($this->data['forms'][$key]['payment']);
        } else {
            $this->data['forms'][$key]['payment'] = $config;
        }
        $this->persist();
    }

    public function deleteView(int $formId, string $slug): void
    {
        $key = (string) $formId;
        $views = $this->data['forms'][$key]['views'] ?? [];
        $this->data['forms'][$key]['views'] = array_values(
            array_filter($views, fn (array $v) => $v['slug'] !== $slug)
        );
        $this->persist();
    }

    public static function slugify(string $label, array $existingSlugs): string
    {
        $base = strtolower((string) preg_replace('/[^A-Za-z0-9]+/', '-', $label));
        $base = trim($base, '-');
        if ($base === '') {
            $base = 'view';
        }

        $slug = $base;
        $i = 2;
        while (in_array($slug, $existingSlugs, true)) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }

    private function load(): array
    {
        if (is_file($this->path)) {
            $json = json_decode((string) file_get_contents($this->path), true);
            if (is_array($json) && isset($json['forms']) && is_array($json['forms'])) {
                return $json;
            }
        }

        $migrated = $this->migrateFromLegacyConfig();
        $this->data = $migrated;
        $this->persist();

        return $migrated;
    }

    private function migrateFromLegacyConfig(): array
    {
        $legacy = is_file($this->legacyFormsPath) ? (require $this->legacyFormsPath) : [];
        $forms = [];

        foreach ($legacy as $formId => $def) {
            $views = [];
            foreach ($def['quick_views'] ?? [] as $qv) {
                $views[] = [
                    'slug'     => $qv['slug'],
                    'label'    => $qv['label'],
                    'group_by' => $qv['field_id'],
                    'columns'  => null,
                ];
            }
            $forms[(string) $formId] = [
                'label' => $def['label'] ?? ('Form ' . $formId),
                'views' => $views,
            ];
        }

        return ['forms' => $forms];
    }

    private function persist(): void
    {
        $dir = dirname($this->path);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        file_put_contents(
            $this->path,
            json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }
}
