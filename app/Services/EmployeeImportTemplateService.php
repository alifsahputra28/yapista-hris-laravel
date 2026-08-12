<?php

namespace App\Services;

use App\Support\Imports\EmployeeImportColumns;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeImportTemplateService
{
    public function download(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $spreadsheet = $this->makeSpreadsheet();
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, 'template-import-pegawai.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    public function makeSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Pegawai');

        foreach (EmployeeImportColumns::headers() as $index => $header) {
            $column = $index + 1;
            $sheet->setCellValueExplicit([$column, 1], $header, DataType::TYPE_STRING);
        }

        $lastColumn = $sheet->getHighestColumn();
        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '02936F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:{$lastColumn}1");

        foreach (range('A', $lastColumn) as $column) {
            $sheet->getColumnDimension($column)->setWidth(22);
        }

        $sheet->getColumnDimension('A')->setWidth(16);
        $sheet->getColumnDimension('B')->setWidth(28);
        $sheet->getColumnDimension('C')->setWidth(30);
        $sheet->getColumnDimension('D')->setWidth(30);
        $sheet->getStyle('A:A')->getNumberFormat()->setFormatCode('@');
        $sheet->getStyle('C:D')->getNumberFormat()->setFormatCode('@');

        $guide = $spreadsheet->createSheet();
        $guide->setTitle('Petunjuk');
        $guide->fromArray([
            ['Petunjuk Import Pegawai'],
            ['Kolom wajib diisi', implode(', ', EmployeeImportColumns::requiredLabels())],
            ['Kolom opsional', implode(', ', EmployeeImportColumns::optionalLabels())],
            ['Jenis Pegawai', implode(', ', EmployeeImportColumns::EMPLOYEE_TYPES)],
            ['Status Kerja', implode(', ', EmployeeImportColumns::EMPLOYMENT_STATUSES)],
            ['Format Tanggal Masuk', 'YYYY-MM-DD'],
            ['Catatan NUP', 'Kosongkan untuk pegawai baru; jika diisi harus tepat 10 digit.'],
        ]);
        $guide->getStyle('A1:B1')->getFont()->setBold(true)->getColor()->setRGB('02936F');
        $guide->getColumnDimension('A')->setWidth(24);
        $guide->getColumnDimension('B')->setWidth(90);
        $guide->getStyle('A:B')->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }
}
