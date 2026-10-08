<?php

namespace App\Services;

use App\Support\OrganizationOptions;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PositionImportTemplateService
{
    public function download(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $spreadsheet = $this->makeSpreadsheet();
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, 'template-import-jabatan.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    public function makeSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Jabatan');
        $headers = ['Nama Jabatan', 'Tipe Jabatan', 'Unit Kerja', 'Status'];
        foreach ($headers as $index => $header) {
            $sheet->setCellValueExplicit([$index + 1, 1], $header, DataType::TYPE_STRING);
        }
        $sheet->fromArray([
            ['Kepala Program Studi K3', 'STR', 'Fakultas Ilmu Kesehatan UIS', 'Aktif'],
            ['Dosen', 'FNG', 'Fakultas Ilmu Kesehatan UIS', 'Aktif'],
            ['Staf Keuangan', 'ADM', 'YAPISTA', 'Aktif'],
            ['Security', 'OPS', 'YAPISTA', 'Aktif'],
            ['Pembina', 'ORG', 'YAPISTA', 'Aktif'],
        ], null, 'A2');
        $sheet->getStyle('A1:D1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '02936F']],
        ]);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:D1');
        foreach (['A' => 32, 'B' => 20, 'C' => 32, 'D' => 16] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        $reference = $spreadsheet->createSheet();
        $reference->setTitle('Referensi Tipe Jabatan');
        $reference->fromArray([['Kode', 'Nama']]);
        foreach (OrganizationOptions::POSITION_TYPES as $code => $option) {
            $reference->fromArray([[$code, $option['name']]], null, 'A'.($reference->getHighestRow() + 1));
        }
        $reference->getStyle('A1:B1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '02936F']],
        ]);
        $reference->getColumnDimension('A')->setWidth(14);
        $reference->getColumnDimension('B')->setWidth(34);

        return $spreadsheet;
    }
}
