<?php

namespace App\Services;

use App\Models\Institution;
use App\Models\Position;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class PositionImportService
{
    private const MAX_ROWS = 1000;

    /** @return array{processed:int,created:int,skipped:int,failed:int,errors:list<string>} */
    public function import(UploadedFile $file): array
    {
        try {
            $reader = IOFactory::createReaderForFile($file->getRealPath());
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file->getRealPath());
            $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
            $spreadsheet->disconnectWorksheets();
        } catch (Throwable) {
            throw ValidationException::withMessages(['file' => 'File tidak dapat diproses. Gunakan template XLSX terbaru.']);
        }

        if (count($rows) < 2) {
            throw ValidationException::withMessages(['file' => 'File tidak berisi data jabatan untuk diimport.']);
        }

        $headers = array_map(fn ($value) => $this->key((string) $value), array_shift($rows));
        $expected = ['nama jabatan', 'unit kerja', 'kategori', 'status'];
        if ($headers !== $expected) {
            throw ValidationException::withMessages(['file' => 'Kolom file tidak sesuai template Jabatan terbaru.']);
        }
        if (count($rows) > self::MAX_ROWS) {
            throw ValidationException::withMessages(['file' => 'File import maksimal berisi '.self::MAX_ROWS.' baris data.']);
        }

        $institutions = Institution::query()->get()->keyBy(fn (Institution $item) => $this->key($item->name));
        $summary = ['processed' => 0, 'created' => 0, 'skipped' => 0, 'failed' => 0, 'errors' => []];

        foreach ($rows as $index => $row) {
            $values = array_map(fn ($value) => trim((string) ($value ?? '')), array_pad($row, 4, null));
            if (implode('', $values) === '') {
                continue;
            }
            $summary['processed']++;
            $line = $index + 2;
            $institution = $institutions->get($this->key($values[1]));
            $validator = Validator::make([
                'name' => $values[0], 'institution' => $institution?->id,
                'type' => $values[2] === '' ? null : mb_strtolower($values[2]),
                'status' => mb_strtolower($values[3]),
            ], [
                'name' => ['required', 'string', 'max:255'],
                'institution' => ['required', 'integer'],
                'type' => ['nullable', Rule::in(['struktural', 'fungsional', 'administratif', 'teknis'])],
                'status' => ['required', Rule::in(['active', 'inactive'])],
            ], [
                'name.required' => 'Nama Jabatan wajib diisi.',
                'institution.required' => 'Unit Kerja tidak ditemukan.',
                'type.in' => 'Kategori Jabatan tidak valid.',
                'status.required' => 'Status wajib diisi.',
                'status.in' => 'Status harus active atau inactive.',
            ]);

            if ($validator->fails()) {
                $summary['failed']++;
                $this->error($summary, $line, $validator->errors()->first());

                continue;
            }

            $name = trim($values[0]);
            $duplicate = Position::query()->where('institution_id', $institution->id)
                ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])->exists();
            if ($duplicate) {
                $summary['skipped']++;
                $this->error($summary, $line, 'Jabatan sudah tersedia pada Unit Kerja tersebut.');

                continue;
            }

            DB::transaction(fn () => Position::create([
                'institution_id' => $institution->id,
                'name' => $name,
                'type' => $values[2] === '' ? null : mb_strtolower($values[2]),
                'status' => mb_strtolower($values[3]),
            ]));
            $summary['created']++;
        }

        if ($summary['processed'] === 0) {
            throw ValidationException::withMessages(['file' => 'File tidak berisi data jabatan untuk diimport.']);
        }

        return $summary;
    }

    private function key(string $value): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $value) ?? $value));
    }

    /** @param array{errors:list<string>} $summary */
    private function error(array &$summary, int $line, string $message): void
    {
        if (count($summary['errors']) < 25) {
            $summary['errors'][] = "Baris {$line} — {$message}";
        }
    }
}
