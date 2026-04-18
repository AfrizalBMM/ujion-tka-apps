<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class SpreadsheetTable
{
    public static function rowsFromUpload(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: '');
        $path = $file->getRealPath();

        if (! $path) {
            throw new RuntimeException('File import tidak valid.');
        }

        return match ($extension) {
            'csv', 'txt' => self::parseCsv($path),
            'xlsx' => self::parseXlsx($path),
            'xls' => self::parseSpreadsheetMl($path),
            default => throw new RuntimeException('Format file belum didukung. Gunakan CSV, XLSX, atau XLS template.'),
        };
    }

    public static function normalizeHeader(string $header): string
    {
        $header = trim(mb_strtolower($header));
        $header = preg_replace('/[^a-z0-9]+/u', '_', $header) ?? '';

        return trim($header, '_');
    }

    private static function parseCsv(string $path): array
    {
        $handle = fopen($path, 'r');

        if (! $handle) {
            throw new RuntimeException('File CSV tidak bisa dibaca.');
        }

        $headers = null;
        $rows = [];

        while (($rawRow = fgetcsv($handle)) !== false) {
            $rawRow = array_map(fn ($value) => is_string($value) ? trim($value) : $value, $rawRow);

            if ($headers === null) {
                $headers = self::normalizeHeaders($rawRow);
                continue;
            }

            if (self::isEmptyRow($rawRow)) {
                continue;
            }

            $rows[] = self::combineRow($headers, $rawRow);
        }

        fclose($handle);

        return $rows;
    }

    private static function parseXlsx(string $path): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Ekstensi ZIP tidak tersedia di server untuk membaca file XLSX.');
        }

        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            throw new RuntimeException('File XLSX tidak bisa dibuka.');
        }

        $sharedStrings = self::parseSharedStrings($zip);
        $worksheetPath = self::resolveFirstWorksheetPath($zip);
        $worksheetXml = $zip->getFromName($worksheetPath);
        $zip->close();

        if (! is_string($worksheetXml) || $worksheetXml === '') {
            throw new RuntimeException('Sheet pertama pada file XLSX tidak ditemukan.');
        }

        $xml = simplexml_load_string($worksheetXml);
        if (! $xml instanceof SimpleXMLElement) {
            throw new RuntimeException('Isi worksheet XLSX tidak valid.');
        }

        $rowsByNumber = [];

        foreach ($xml->sheetData->row ?? [] as $rowNode) {
            $rowNumber = (int) ($rowNode['r'] ?? 0);
            $rowData = [];

            foreach ($rowNode->c as $cell) {
                $reference = (string) ($cell['r'] ?? '');
                $columnIndex = self::columnIndexFromReference($reference);
                $type = (string) ($cell['t'] ?? '');
                $value = '';

                if ($type === 'inlineStr') {
                    $value = trim((string) ($cell->is->t ?? ''));
                } else {
                    $rawValue = (string) ($cell->v ?? '');
                    if ($type === 's') {
                        $value = $sharedStrings[(int) $rawValue] ?? '';
                    } else {
                        $value = trim($rawValue);
                    }
                }

                $rowData[$columnIndex] = $value;
            }

            if ($rowNumber > 0) {
                $rowsByNumber[$rowNumber] = $rowData;
            }
        }

        ksort($rowsByNumber);

        return self::normalizeIndexedRows(array_values($rowsByNumber));
    }

    private static function parseSpreadsheetMl(string $path): array
    {
        $xml = simplexml_load_file($path);

        if (! $xml instanceof SimpleXMLElement) {
            throw new RuntimeException('File XLS template tidak valid.');
        }

        $namespaces = $xml->getNamespaces(true);
        $spreadsheetNs = $namespaces['ss'] ?? 'urn:schemas-microsoft-com:office:spreadsheet';

        $rows = [];
        $worksheets = $xml->Worksheet ?? [];
        $firstWorksheet = $worksheets[0] ?? null;
        $table = $firstWorksheet?->Table;

        if (! $table) {
            throw new RuntimeException('Sheet pertama pada template XLS tidak ditemukan.');
        }

        foreach ($table->Row as $rowNode) {
            $cells = [];
            $columnIndex = 1;

            foreach ($rowNode->Cell as $cellNode) {
                $attributes = $cellNode->attributes($spreadsheetNs, true);
                if (isset($attributes['Index'])) {
                    $columnIndex = (int) $attributes['Index'];
                }

                $cells[$columnIndex] = trim((string) ($cellNode->Data ?? ''));
                $columnIndex++;
            }

            $rows[] = $cells;
        }

        return self::normalizeIndexedRows($rows);
    }

    private static function normalizeIndexedRows(array $indexedRows): array
    {
        if ($indexedRows === []) {
            return [];
        }

        $headerRow = array_shift($indexedRows) ?: [];
        ksort($headerRow);
        $headers = self::normalizeHeaders(array_values($headerRow));
        $rows = [];

        foreach ($indexedRows as $row) {
            if (! is_array($row) || $row === []) {
                continue;
            }

            ksort($row);
            $maxColumn = max(array_keys($row));
            $values = [];

            for ($column = 1; $column <= $maxColumn; $column++) {
                $values[] = isset($row[$column]) ? trim((string) $row[$column]) : '';
            }

            if (self::isEmptyRow($values)) {
                continue;
            }

            $rows[] = self::combineRow($headers, $values);
        }

        return $rows;
    }

    private static function parseSharedStrings(ZipArchive $zip): array
    {
        $xmlContent = $zip->getFromName('xl/sharedStrings.xml');

        if (! is_string($xmlContent) || $xmlContent === '') {
            return [];
        }

        $xml = simplexml_load_string($xmlContent);
        if (! $xml instanceof SimpleXMLElement) {
            return [];
        }

        $strings = [];

        foreach ($xml->si as $item) {
            $text = '';

            if (isset($item->t)) {
                $text = (string) $item->t;
            } else {
                foreach ($item->r as $run) {
                    $text .= (string) ($run->t ?? '');
                }
            }

            $strings[] = trim($text);
        }

        return $strings;
    }

    private static function resolveFirstWorksheetPath(ZipArchive $zip): string
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if (! is_string($workbookXml) || ! is_string($relsXml)) {
            return 'xl/worksheets/sheet1.xml';
        }

        $workbook = simplexml_load_string($workbookXml);
        $rels = simplexml_load_string($relsXml);

        if (! $workbook instanceof SimpleXMLElement || ! $rels instanceof SimpleXMLElement) {
            return 'xl/worksheets/sheet1.xml';
        }

        $relationships = [];
        foreach ($rels->Relationship as $relationship) {
            $relationships[(string) $relationship['Id']] = (string) $relationship['Target'];
        }

        $sheet = $workbook->sheets->sheet[0] ?? null;
        $attributes = $sheet?->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $relationshipId = (string) ($attributes['id'] ?? '');

        if ($relationshipId !== '' && isset($relationships[$relationshipId])) {
            $target = $relationships[$relationshipId];
            return str_starts_with($target, 'xl/') ? $target : 'xl/' . ltrim($target, '/');
        }

        return 'xl/worksheets/sheet1.xml';
    }

    private static function normalizeHeaders(array $headers): array
    {
        return array_map(
            fn ($header, $index) => self::normalizeHeader((string) $header) ?: 'column_' . ($index + 1),
            array_values($headers),
            array_keys(array_values($headers))
        );
    }

    private static function combineRow(array $headers, array $rawRow): array
    {
        $normalizedValues = array_map(
            fn ($value) => is_string($value) ? trim($value) : trim((string) $value),
            array_values($rawRow)
        );

        if (count($normalizedValues) < count($headers)) {
            $normalizedValues = array_pad($normalizedValues, count($headers), '');
        }

        return array_combine($headers, array_slice($normalizedValues, 0, count($headers))) ?: [];
    }

    private static function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private static function columnIndexFromReference(string $reference): int
    {
        if ($reference === '') {
            return 1;
        }

        preg_match('/[A-Z]+/i', $reference, $matches);
        $letters = strtoupper($matches[0] ?? 'A');
        $index = 0;

        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return max(1, $index);
    }
}
