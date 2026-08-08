<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Services\EmployeeNikProtectionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BackfillEmployeeNik extends Command
{
    protected $signature = 'employee-security:backfill-nik
        {--dry-run : Inspect records without changing data}
        {--commit : Encrypt valid legacy NIK records}';

    protected $description = 'Backfill legacy employee NIK values into encrypted storage and blind indexes';

    public function handle(EmployeeNikProtectionService $service): int
    {
        if ($this->option('dry-run') && $this->option('commit')) {
            $this->error('Gunakan salah satu opsi --dry-run atau --commit.');

            return self::INVALID;
        }

        $commit = (bool) $this->option('commit');
        $counts = [
            'checked' => 0,
            'ready' => 0,
            'migrated' => 0,
            'skipped' => 0,
            'invalid' => 0,
            'conflicts' => 0,
        ];

        Employee::query()
            ->whereNotNull('nik')
            ->orderBy('id')
            ->chunkById(200, function ($employees) use ($service, $commit, &$counts): void {
                foreach ($employees as $employee) {
                    $counts['checked']++;
                    $legacyNik = $employee->getRawOriginal('nik');

                    if (filled($employee->getRawOriginal('nik_encrypted')) && filled($employee->getRawOriginal('nik_lookup'))) {
                        $counts['skipped']++;
                        continue;
                    }

                    try {
                        $normalized = $service->normalize(is_string($legacyNik) ? $legacyNik : null);
                        $lookup = $service->lookup($normalized);
                    } catch (InvalidArgumentException) {
                        $counts['invalid']++;
                        continue;
                    }

                    if ($normalized === null || $lookup === null) {
                        $counts['invalid']++;
                        continue;
                    }

                    $conflict = Employee::query()
                        ->where('nik_lookup', $lookup)
                        ->whereKeyNot($employee->id)
                        ->exists();

                    if ($conflict) {
                        $counts['conflicts']++;
                        continue;
                    }

                    $counts['ready']++;

                    if (! $commit) {
                        continue;
                    }

                    DB::transaction(function () use ($employee, $normalized): void {
                        $lockedEmployee = Employee::query()->lockForUpdate()->findOrFail($employee->id);

                        if ($lockedEmployee->getRawOriginal('nik') === null) {
                            return;
                        }

                        $lockedEmployee->nik = $normalized;
                        $lockedEmployee->save();
                    });
                    $counts['migrated']++;
                }
            });

        $this->table(['Metric', 'Count'], [
            ['Records checked', $counts['checked']],
            ['Ready', $counts['ready']],
            ['Migrated', $counts['migrated']],
            ['Skipped', $counts['skipped']],
            ['Invalid format', $counts['invalid']],
            ['Duplicate conflict', $counts['conflicts']],
        ]);
        $this->info($commit ? 'Backfill NIK selesai.' : 'Dry-run selesai. Tidak ada data yang diubah.');

        return self::SUCCESS;
    }
}
