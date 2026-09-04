<?php

declare(strict_types=1);

/**
 * Writes a single-sheet .xlsx file with no external dependencies (just the
 * PHP zip extension). Values are written as inline strings, which keeps
 * the OOXML minimal and avoids a shared-strings table — plenty for
 * flat tabular exports of form data.
 */
final class XlsxWriter
{
    /**
     * @param string[] $headers
     * @param iterable<int, string[]> $rows
     */
    public static function stream(string $filename, array $headers, iterable $rows): void
    {
        $tmpPath = tempnam(sys_get_temp_dir(), 'xlsx');
        if ($tmpPath === false) {
            throw new RuntimeException('Could not create a temporary file for the export.');
        }

        try {
            self::write($tmpPath, $headers, $rows);

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
            header('Content-Length: ' . (string) filesize($tmpPath));
            header('Cache-Control: max-age=0');

            readfile($tmpPath);
        } finally {
            unlink($tmpPath);
        }
    }

    /**
     * @param string[] $headers
     * @param iterable<int, string[]> $rows
     */
    public static function write(string $path, array $headers, iterable $rows): void
    {
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Could not create xlsx archive at {$path}.");
        }

        $zip->addFromString('[Content_Types].xml', self::contentTypesXml());
        $zip->addFromString('_rels/.rels', self::packageRelsXml());
        $zip->addFromString('xl/workbook.xml', self::workbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRelsXml());
        $zip->addFromString('xl/styles.xml', self::stylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', self::sheetXml($headers, $rows));

        $zip->close();
    }

    private static function sheetXml(array $headers, iterable $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $xml .= '<cols><col min="1" max="60" width="20" customWidth="1"/></cols>';
        $xml .= '<sheetData>';

        $xml .= self::rowXml(1, $headers, styleIndex: 1);

        $rowNum = 2;
        foreach ($rows as $row) {
            $xml .= self::rowXml($rowNum, $row, styleIndex: 0);
            $rowNum++;
        }

        $xml .= '</sheetData></worksheet>';

        return $xml;
    }

    private static function rowXml(int $rowNum, array $cells, int $styleIndex): string
    {
        $xml = '<row r="' . $rowNum . '">';

        foreach (array_values($cells) as $i => $value) {
            $ref = self::columnLetter($i + 1) . $rowNum;
            $styleAttr = $styleIndex > 0 ? ' s="' . $styleIndex . '"' : '';
            $xml .= '<c r="' . $ref . '" t="inlineStr"' . $styleAttr . '><is><t xml:space="preserve">'
                . self::escape((string) $value) . '</t></is></c>';
        }

        return $xml . '</row>';
    }

    /**
     * Streams a multi-sheet .xlsx built from SheetBuilder's normalized
     * output — one worksheet per $sheets entry:
     *   ['label' => string, 'layout' => 'flat'|'stacked'|'side_by_side', 'blocks' => [...]]
     * Each block is ['heading' => ?string, 'headers' => string[], 'rows' => string[][]].
     *
     * @param array[] $sheets
     */
    public static function streamWorkbook(string $filename, array $sheets): void
    {
        $tmpPath = tempnam(sys_get_temp_dir(), 'xlsx');
        if ($tmpPath === false) {
            throw new RuntimeException('Could not create a temporary file for the export.');
        }

        try {
            self::writeWorkbook($tmpPath, $sheets);

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
            header('Content-Length: ' . (string) filesize($tmpPath));
            header('Cache-Control: max-age=0');

            readfile($tmpPath);
        } finally {
            unlink($tmpPath);
        }
    }

    /**
     * @param array[] $sheets see streamWorkbook()
     */
    public static function writeWorkbook(string $path, array $sheets): void
    {
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Could not create xlsx archive at {$path}.");
        }

        $names = [];
        $used = [];
        foreach ($sheets as $sheet) {
            $names[] = self::sanitizeSheetName($sheet['label'], $used);
        }

        $zip->addFromString('[Content_Types].xml', self::contentTypesXmlMulti(count($sheets)));
        $zip->addFromString('_rels/.rels', self::packageRelsXml());
        $zip->addFromString('xl/workbook.xml', self::workbookXmlMulti($names));
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRelsXmlMulti(count($sheets)));
        $zip->addFromString('xl/styles.xml', self::stylesXml());

        foreach (array_values($sheets) as $i => $sheet) {
            $xml = match ($sheet['layout']) {
                'stacked'      => self::stackedSheetXml($sheet['blocks']),
                'side_by_side' => self::sideBySideSheetXml($sheet['blocks']),
                default        => self::sheetXml($sheet['blocks'][0]['headers'], $sheet['blocks'][0]['rows']),
            };
            $zip->addFromString('xl/worksheets/sheet' . ($i + 1) . '.xml', $xml);
        }

        $zip->close();
    }

    private static function stackedSheetXml(array $blocks): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $xml .= '<cols><col min="1" max="60" width="20" customWidth="1"/></cols>';
        $xml .= '<sheetData>';

        $rowNum = 1;
        foreach ($blocks as $block) {
            if ($block['heading'] !== null) {
                $xml .= '<row r="' . $rowNum . '">' . self::cellsXml(1, $rowNum, [$block['heading']], 1) . '</row>';
                $rowNum++;
            }

            $xml .= '<row r="' . $rowNum . '">' . self::cellsXml(1, $rowNum, $block['headers'], 1) . '</row>';
            $rowNum++;

            $rows = $block['rows'] !== [] ? $block['rows'] : [['No data available.']];
            foreach ($rows as $row) {
                $xml .= '<row r="' . $rowNum . '">' . self::cellsXml(1, $rowNum, $row, 0) . '</row>';
                $rowNum++;
            }

            $rowNum++; // blank separator row before the next block
        }

        $xml .= '</sheetData></worksheet>';

        return $xml;
    }

    private static function sideBySideSheetXml(array $blocks): string
    {
        $offsets = [];
        $col = 1;
        $maxRows = 0;
        foreach ($blocks as $block) {
            $offsets[] = $col;
            $col += count($block['headers']) + 1; // +1 blank gap column
            $maxRows = max($maxRows, count($block['rows']));
        }

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $xml .= '<cols><col min="1" max="200" width="20" customWidth="1"/></cols>';
        $xml .= '<sheetData>';

        $xml .= '<row r="1">';
        foreach ($blocks as $i => $block) {
            $xml .= self::cellsXml($offsets[$i], 1, $block['headers'], 1);
        }
        $xml .= '</row>';

        for ($r = 0; $r < $maxRows; $r++) {
            $rowNum = $r + 2;
            $xml .= '<row r="' . $rowNum . '">';
            foreach ($blocks as $i => $block) {
                if (isset($block['rows'][$r])) {
                    $xml .= self::cellsXml($offsets[$i], $rowNum, $block['rows'][$r], 0);
                }
            }
            $xml .= '</row>';
        }

        $xml .= '</sheetData></worksheet>';

        return $xml;
    }

    private static function cellsXml(int $colOffset, int $rowNum, array $cells, int $styleIndex): string
    {
        $xml = '';
        foreach (array_values($cells) as $i => $value) {
            $ref = self::columnLetter($colOffset + $i) . $rowNum;
            $styleAttr = $styleIndex > 0 ? ' s="' . $styleIndex . '"' : '';
            $xml .= '<c r="' . $ref . '" t="inlineStr"' . $styleAttr . '><is><t xml:space="preserve">'
                . self::escape((string) $value) . '</t></is></c>';
        }

        return $xml;
    }

    /**
     * Excel sheet names: max 31 chars, no : \ / ? * [ ], and unique within
     * the workbook (mirrors ConfigStore::slugify's dedupe approach).
     *
     * @param string[] $used
     */
    private static function sanitizeSheetName(string $name, array &$used): string
    {
        $clean = preg_replace('/[:\\\\\/\?\*\[\]]/', '', $name) ?? $name;
        $clean = trim($clean);
        if ($clean === '') {
            $clean = 'Sheet';
        }
        $clean = mb_substr($clean, 0, 31);

        $base = $clean;
        $i = 2;
        while (in_array($clean, $used, true)) {
            $suffix = ' (' . $i . ')';
            $clean = mb_substr($base, 0, 31 - mb_strlen($suffix)) . $suffix;
            $i++;
        }

        $used[] = $clean;

        return $clean;
    }

    private static function contentTypesXmlMulti(int $count): string
    {
        $overrides = '';
        for ($i = 1; $i <= $count; $i++) {
            $overrides .= '<Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . $overrides
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';
    }

    /**
     * @param string[] $names already-sanitized, unique sheet names
     */
    private static function workbookXmlMulti(array $names): string
    {
        $sheetsXml = '';
        foreach ($names as $i => $name) {
            $id = $i + 1;
            $sheetsXml .= '<sheet name="' . self::escape($name) . '" sheetId="' . $id . '" r:id="rId' . $id . '"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . $sheetsXml . '</sheets>'
            . '</workbook>';
    }

    private static function workbookRelsXmlMulti(int $count): string
    {
        $rels = '';
        for ($i = 1; $i <= $count; $i++) {
            $rels .= '<Relationship Id="rId' . $i . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $i . '.xml"/>';
        }
        $rels .= '<Relationship Id="rId' . ($count + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . $rels
            . '</Relationships>';
    }

    private static function escape(string $value): string
    {
        // Strip control characters not permitted in XML 1.0 (aside from tab/LF/CR).
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value) ?? $value;

        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    private static function columnLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)) . $letter;
            $index = intdiv($index, 26);
        }

        return $letter;
    }

    private static function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';
    }

    private static function packageRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private static function workbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Data" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private static function workbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    private static function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="2"><fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="2">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '</cellXfs>'
            . '</styleSheet>';
    }
}
