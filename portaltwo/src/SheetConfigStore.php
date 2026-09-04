<?php

declare(strict_types=1);

/**
 * Persistent, admin-editable store for each form's "Sheets" definitions —
 * the same idea as ConfigStore's "views", but for the derived/pivoted
 * breakdowns SheetBuilder computes (group-by columns, per-category
 * sections, presence columns).
 *
 * Backed by data/sheets.json rather than a database table, for the same
 * reasons as ConfigStore: small, human-inspectable, no migration tooling
 * needed. On first use, if that file doesn't exist yet, it's seeded from
 * config/sheets.php so a hand-edited starting config isn't lost — from
 * then on the JSON file is authoritative and config/sheets.php is no
 * longer read.
 */
final class SheetConfigStore
{
    private array $data;

    public function __construct(private string $path, private string $legacyPath)
    {
        $this->data = $this->load();
    }

    public function sheetsForForm(int $formId): array
    {
        return $this->data['forms'][(string) $formId]['sheets'] ?? [];
    }

    /**
     * @param array $sheet ['slug'=>?, 'type'=>string, 'label'=>string, ...type-specific keys]
     *                      slug is generated from the label when empty (new sheet).
     */
    public function saveSheet(int $formId, array $sheet): void
    {
        $key = (string) $formId;
        $this->data['forms'][$key]['sheets'] ??= [];
        $sheets = $this->data['forms'][$key]['sheets'];

        if (($sheet['slug'] ?? '') === '') {
            $existingSlugs = array_column($sheets, 'slug');
            $sheet['slug'] = self::slugify($sheet['label'], $existingSlugs);
        }

        $found = false;
        foreach ($sheets as $i => $existing) {
            if ($existing['slug'] === $sheet['slug']) {
                $sheets[$i] = $sheet;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $sheets[] = $sheet;
        }

        $this->data['forms'][$key]['sheets'] = $sheets;
        $this->persist();
    }

    public function deleteSheet(int $formId, string $slug): void
    {
        $key = (string) $formId;
        $sheets = $this->data['forms'][$key]['sheets'] ?? [];
        $this->data['forms'][$key]['sheets'] = array_values(
            array_filter($sheets, fn (array $s) => $s['slug'] !== $slug)
        );
        $this->persist();
    }

    public static function slugify(string $label, array $existingSlugs): string
    {
        $base = strtolower((string) preg_replace('/[^A-Za-z0-9]+/', '-', $label));
        $base = trim($base, '-');
        if ($base === '') {
            $base = 'sheet';
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
        $legacy = is_file($this->legacyPath) ? (require $this->legacyPath) : [];
        $forms = [];

        foreach ($legacy as $formId => $def) {
            $sheets = [];
            foreach ($def['sheets'] ?? [] as $sheet) {
                if (($sheet['slug'] ?? '') === '') {
                    $sheet['slug'] = self::slugify($sheet['label'] ?? 'sheet', array_column($sheets, 'slug'));
                }
                $sheets[] = $sheet;
            }
            $forms[(string) $formId] = ['sheets' => $sheets];
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
