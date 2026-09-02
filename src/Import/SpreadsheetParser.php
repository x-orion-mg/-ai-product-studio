<?php

declare(strict_types=1);

namespace AIProductStudio\Import;

use AIProductStudio\Exceptions\ValidationException;
use ZipArchive;

/**
 * Reads a CSV or XLSX spreadsheet into associative rows.
 */
final class SpreadsheetParser
{
    public const MAX_ROWS = 50;

    /**
     * @return array<int, array<string, string>>
     *
     * @throws ValidationException
     */
    public function parse(string $path, string $filename): array
    {
        $extension = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));

        $rows = match ($extension) {
            'csv', 'txt' => $this->parseCsv($path),
            'xlsx'       => $this->parseXlsx($path),
            'xls'        => throw new ValidationException(
                __('Les fichiers .xls anciens ne sont pas supportés. Enregistrez le fichier en .xlsx ou .csv.', 'ai-product-studio')
            ),
            default      => throw new ValidationException(
                __('Format non supporté. Utilisez un fichier CSV ou Excel (.xlsx).', 'ai-product-studio')
            ),
        };

        if ($rows === []) {
            throw new ValidationException(
                __('Le fichier ne contient aucune ligne exploitable.', 'ai-product-studio')
            );
        }

        if (count($rows) > self::MAX_ROWS) {
            throw new ValidationException(
                sprintf(
                    /* translators: %d: max rows. */
                    __('L\'import est limité à %d lignes. Découpez le fichier.', 'ai-product-studio'),
                    self::MAX_ROWS
                )
            );
        }

        return $this->normalizeRows($rows);
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function parseCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new ValidationException(__('Impossible de lire le fichier CSV.', 'ai-product-studio'));
        }

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);

            throw new ValidationException(__('Le fichier CSV est vide.', 'ai-product-studio'));
        }

        $firstLine = preg_replace('/^\xEF\xBB\xBF/', '', $firstLine) ?? $firstLine;
        $delimiter = $this->detectDelimiter($firstLine);
        rewind($handle);

        $headers = null;
        $rows    = [];

        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            if ($data === [null] || $data === false) {
                continue;
            }

            if ($headers === null) {
                $headers = $this->normalizeHeaders($data);
                continue;
            }

            if ($this->isEmptyRow($data)) {
                continue;
            }

            $rows[] = $this->combine($headers, $data);
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function parseXlsx(string $path): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new ValidationException(__('L\'extension PHP Zip est requise pour lire un fichier Excel.', 'ai-product-studio'));
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new ValidationException(__('Impossible d\'ouvrir le fichier Excel.', 'ai-product-studio'));
        }

        $sharedStrings = $this->xlsxSharedStrings($zip);
        $sheetXml      = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if (! is_string($sheetXml) || $sheetXml === '') {
            throw new ValidationException(__('La première feuille Excel est introuvable.', 'ai-product-studio'));
        }

        $xml = @simplexml_load_string($sheetXml);
        if ($xml === false) {
            throw new ValidationException(__('Feuille Excel illisible.', 'ai-product-studio'));
        }

        $xml->registerXPathNamespace('a', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $rowNodes = $xml->xpath('//a:sheetData/a:row') ?: [];

        $matrix  = [];
        $maxCol  = 0;

        foreach ($rowNodes as $rowNode) {
            $rowIndex = ((int) $rowNode['r']) - 1;
            if ($rowIndex < 0) {
                $rowIndex = count($matrix);
            }

            foreach ($rowNode->c as $cell) {
                $ref   = (string) $cell['r'];
                $col   = $this->columnIndexFromRef($ref);
                $value = $this->xlsxCellValue($cell, $sharedStrings);
                $matrix[$rowIndex][$col] = $value;
                $maxCol = max($maxCol, $col);
            }
        }

        if ($matrix === []) {
            return [];
        }

        ksort($matrix);
        $firstKey = array_key_first($matrix);
        $headerRow = $matrix[$firstKey] ?? [];
        unset($matrix[$firstKey]);

        $headerCells = [];
        for ($i = 0; $i <= $maxCol; $i++) {
            $headerCells[] = (string) ($headerRow[$i] ?? '');
        }
        $headers = $this->normalizeHeaders($headerCells);

        $rows = [];
        foreach ($matrix as $row) {
            $cells = [];
            for ($i = 0; $i <= $maxCol; $i++) {
                $cells[] = (string) ($row[$i] ?? '');
            }
            if ($this->isEmptyRow($cells)) {
                continue;
            }
            $rows[] = $this->combine($headers, $cells);
        }

        return $rows;
    }

    /**
     * @return array<int, string>
     */
    private function xlsxSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if (! is_string($xml) || $xml === '') {
            return [];
        }

        $doc = @simplexml_load_string($xml);
        if ($doc === false) {
            return [];
        }

        $doc->registerXPathNamespace('a', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $items = $doc->xpath('//a:si') ?: [];
        $out   = [];

        foreach ($items as $si) {
            $texts = $si->xpath('.//a:t') ?: [];
            $out[] = implode('', array_map(static fn ($t): string => (string) $t, $texts));
        }

        return $out;
    }

    /**
     * @param array<int, string> $sharedStrings
     */
    private function xlsxCellValue(\SimpleXMLElement $cell, array $sharedStrings): string
    {
        $type = (string) $cell['t'];
        $raw  = isset($cell->v) ? (string) $cell->v : '';

        if ($type === 's') {
            $index = (int) $raw;

            return $sharedStrings[$index] ?? '';
        }

        if ($type === 'inlineStr') {
            $texts = $cell->xpath('.//*[local-name()="t"]') ?: [];

            return implode('', array_map(static fn ($t): string => (string) $t, $texts));
        }

        return $raw;
    }

    private function columnIndexFromRef(string $ref): int
    {
        if (preg_match('/^([A-Z]+)/i', $ref, $matches) !== 1) {
            return 0;
        }

        $letters = strtoupper($matches[1]);
        $index   = 0;
        $length  = strlen($letters);

        for ($i = 0; $i < $length; $i++) {
            $index = ($index * 26) + (ord($letters[$i]) - 64);
        }

        return max(0, $index - 1);
    }

    /**
     * @param array<int, array<string, string>> $rows
     *
     * @return array<int, array<string, string>>
     */
    private function normalizeRows(array $rows): array
    {
        $out = [];

        foreach ($rows as $row) {
            $mapped = [
                'title'           => $this->first($row, ['title', 'titre', 'nom', 'name', 'product_name']),
                'description'     => $this->first($row, ['description', 'desc', 'description_produit', 'product_description', 'texte']),
                'price'           => $this->first($row, ['price', 'prix', 'regular_price']),
                'sale_price'      => $this->first($row, ['sale_price', 'prix_promo', 'promo', 'promotion']),
                'related_ids'     => $this->first($row, ['related_ids', 'related', 'produits_associes', 'upsells']),
            ];

            if ($mapped['description'] === '' && $mapped['title'] === '') {
                continue;
            }

            if ($mapped['description'] === '') {
                $mapped['description'] = $mapped['title'];
            }

            $out[] = $mapped;
        }

        if ($out === []) {
            throw new ValidationException(
                __('Aucune ligne n\'a de colonne « description » ou « title ».', 'ai-product-studio')
            );
        }

        return $out;
    }

    /**
     * @param array<string, string> $row
     * @param array<int, string>    $keys
     */
    private function first(array $row, array $keys): string
    {
        foreach ($keys as $key) {
            if (isset($row[$key]) && trim($row[$key]) !== '') {
                return trim($row[$key]);
            }
        }

        return '';
    }

    /**
     * @param array<int, string|null> $headers
     *
     * @return array<int, string>
     */
    private function normalizeHeaders(array $headers): array
    {
        $out = [];

        foreach ($headers as $index => $header) {
            $key = strtolower(trim((string) $header));
            $key = preg_replace('/[^\p{L}\p{N}]+/u', '_', $key) ?? $key;
            $key = trim($key, '_');
            $out[$index] = $key !== '' ? $key : 'col_' . $index;
        }

        return $out;
    }

    /**
     * @param array<int, string>      $headers
     * @param array<int, string|null> $data
     *
     * @return array<string, string>
     */
    private function combine(array $headers, array $data): array
    {
        $row = [];

        foreach ($headers as $index => $header) {
            $row[$header] = trim((string) ($data[$index] ?? ''));
        }

        return $row;
    }

    /**
     * @param array<int, string|null> $data
     */
    private function isEmptyRow(array $data): bool
    {
        foreach ($data as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    private function detectDelimiter(string $line): string
    {
        $candidates = [',' => 0, ';' => 0, "\t" => 0];

        foreach ($candidates as $delimiter => $_count) {
            $candidates[$delimiter] = count(str_getcsv($line, $delimiter));
        }

        arsort($candidates);
        $best = (string) array_key_first($candidates);

        return $candidates[$best] > 1 ? $best : ',';
    }
}
