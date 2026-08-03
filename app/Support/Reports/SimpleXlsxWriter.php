<?php

namespace App\Support\Reports;

use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SimpleXlsxWriter
{
    /**
     * @param  array<int, string>  $headings
     * @param  iterable<int, array<int, mixed>>  $rows
     */
    public static function download(string $filename, array $headings, iterable $rows, string $sheetName = 'Report'): StreamedResponse
    {
        $content = self::make($headings, $rows, $sheetName);
        $filename = self::sanitizeFilename($filename);

        return response()->streamDownload(
            static function () use ($content): void {
                echo $content;
            },
            $filename,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'max-age=0, no-cache, must-revalidate, proxy-revalidate',
            ]
        );
    }

    /**
     * @param  array<int, string>  $headings
     * @param  iterable<int, array<int, mixed>>  $rows
     */
    public static function make(array $headings, iterable $rows, string $sheetName = 'Report'): string
    {
        $sheetXml = self::sheetXml($headings, $rows);
        $sheetName = self::sanitizeSheetName($sheetName);

        return self::zip([
            '[Content_Types].xml' => self::contentTypesXml(),
            '_rels/.rels' => self::rootRelationshipsXml(),
            'xl/workbook.xml' => self::workbookXml($sheetName),
            'xl/_rels/workbook.xml.rels' => self::workbookRelationshipsXml(),
            'xl/worksheets/sheet1.xml' => $sheetXml,
        ]);
    }

    /**
     * @param  array<int, string>  $headings
     * @param  iterable<int, array<int, mixed>>  $rows
     */
    private static function sheetXml(array $headings, iterable $rows): string
    {
        $xmlRows = [];
        $rowNumber = 1;
        $xmlRows[] = self::rowXml($rowNumber, $headings);

        foreach ($rows as $row) {
            $rowNumber++;
            $xmlRows[] = self::rowXml($rowNumber, array_values($row));
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetData>'
            .implode('', $xmlRows)
            .'</sheetData>'
            .'</worksheet>';
    }

    /**
     * @param  array<int, mixed>  $values
     */
    private static function rowXml(int $rowNumber, array $values): string
    {
        $cells = [];

        foreach ($values as $index => $value) {
            $cellReference = self::columnName($index + 1).$rowNumber;
            $text = self::escapeCellValue($value);
            $cells[] = '<c r="'.$cellReference.'" t="inlineStr"><is><t>'.$text.'</t></is></c>';
        }

        return '<row r="'.$rowNumber.'">'.implode('', $cells).'</row>';
    }

    private static function columnName(int $index): string
    {
        $name = '';

        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)).$name;
            $index = intdiv($index, 26);
        }

        return $name;
    }

    private static function escapeCellValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $text = (string) $value;
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $text) ?? '';

        return htmlspecialchars($text, ENT_XML1 | ENT_COMPAT | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function sanitizeSheetName(string $sheetName): string
    {
        $sheetName = preg_replace('/[\[\]\:\*\?\/\\\\]/', ' ', $sheetName) ?? 'Report';
        $sheetName = trim($sheetName);

        return Str::substr($sheetName !== '' ? $sheetName : 'Report', 0, 31);
    }

    private static function sanitizeFilename(string $filename): string
    {
        $filename = basename(str_replace('\\', '/', $filename));
        $filename = preg_replace('/[\x00-\x1F\x7F<>:"\/\\|?*]+/u', '-', $filename) ?? '';
        $filename = trim($filename, " .-");
        $filename = $filename !== '' ? $filename : 'report.xlsx';

        return Str::endsWith(Str::lower($filename), '.xlsx')
            ? $filename
            : $filename.'.xlsx';
    }

    private static function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'</Types>';
    }

    private static function rootRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private static function workbookXml(string $sheetName): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets>'
            .'<sheet name="'.self::escapeCellValue($sheetName).'" sheetId="1" r:id="rId1"/>'
            .'</sheets>'
            .'</workbook>';
    }

    private static function workbookRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'</Relationships>';
    }

    /**
     * Create a ZIP archive using stored entries, avoiding the ext-zip dependency.
     *
     * @param  array<string, string>  $files
     */
    private static function zip(array $files): string
    {
        $zip = '';
        $centralDirectory = '';
        $fileCount = 0;
        [$dosTime, $dosDate] = self::dosDateTime();

        foreach ($files as $filename => $contents) {
            $fileCount++;
            $offset = strlen($zip);
            $size = strlen($contents);
            $crc = (int) sprintf('%u', crc32($contents));
            $filenameLength = strlen($filename);

            $zip .= pack(
                'VvvvvvVVVvv',
                0x04034b50,
                20,
                0,
                0,
                $dosTime,
                $dosDate,
                $crc,
                $size,
                $size,
                $filenameLength,
                0
            );
            $zip .= $filename.$contents;

            $centralDirectory .= pack(
                'VvvvvvvVVVvvvvvVV',
                0x02014b50,
                20,
                20,
                0,
                0,
                $dosTime,
                $dosDate,
                $crc,
                $size,
                $size,
                $filenameLength,
                0,
                0,
                0,
                0,
                0,
                $offset
            );
            $centralDirectory .= $filename;
        }

        $centralDirectoryOffset = strlen($zip);
        $centralDirectorySize = strlen($centralDirectory);

        return $zip
            .$centralDirectory
            .pack(
                'VvvvvVVv',
                0x06054b50,
                0,
                0,
                $fileCount,
                $fileCount,
                $centralDirectorySize,
                $centralDirectoryOffset,
                0
            );
    }

    /**
     * @return array{0: int, 1: int}
     */
    private static function dosDateTime(): array
    {
        $timestamp = now();
        $year = max((int) $timestamp->format('Y'), 1980);
        $time = ((int) $timestamp->format('H') << 11)
            | ((int) $timestamp->format('i') << 5)
            | intdiv((int) $timestamp->format('s'), 2);
        $date = (($year - 1980) << 9)
            | ((int) $timestamp->format('n') << 5)
            | (int) $timestamp->format('j');

        return [$time, $date];
    }
}
