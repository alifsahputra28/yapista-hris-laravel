<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Services\EmployeePhotoStorageService;
use Illuminate\Console\Command;

class MigrateEmployeePhotosToPrivate extends Command
{
    protected $signature = 'employee-security:migrate-photos-private
        {--dry-run : Inspect legacy public photos without changing files}
        {--commit : Move legacy public photos to private storage}';

    protected $description = 'Inspect or move legacy employee photos from public to private storage';

    public function handle(EmployeePhotoStorageService $storage): int
    {
        if ($this->option('dry-run') && $this->option('commit')) {
            $this->error('Pilih salah satu mode: --dry-run atau --commit.');

            return self::INVALID;
        }

        $commit = (bool) $this->option('commit');
        $counts = [
            'checked' => 0,
            'already_private' => 0,
            'ready' => 0,
            'migrated' => 0,
            'missing_or_invalid' => 0,
            'failed' => 0,
        ];

        Employee::query()
            ->whereNotNull('photo')
            ->select(['id', 'photo'])
            ->chunkById(200, function ($employees) use ($storage, $commit, &$counts): void {
                foreach ($employees as $employee) {
                    $counts['checked']++;
                    $location = $storage->locate($employee->photo);

                    if ($location === null) {
                        $counts['missing_or_invalid']++;
                        continue;
                    }

                    if ($location['disk'] === EmployeePhotoStorageService::PRIVATE_DISK) {
                        $counts['already_private']++;

                        if ($storage->legacyPublicExists($employee->photo)) {
                            $counts['ready']++;

                            if ($commit) {
                                $storage->moveLegacyToPrivate($employee->photo)
                                    ? $counts['migrated']++
                                    : $counts['failed']++;
                            }
                        }

                        continue;
                    }

                    $counts['ready']++;

                    if ($commit) {
                        $storage->moveLegacyToPrivate($employee->photo)
                            ? $counts['migrated']++
                            : $counts['failed']++;
                    }
                }
            });

        $this->line('Mode: '.($commit ? 'commit' : 'dry-run'));
        $this->line('Records checked: '.$counts['checked']);
        $this->line('Already private: '.$counts['already_private']);
        $this->line('Ready: '.$counts['ready']);
        $this->line('Migrated: '.$counts['migrated']);
        $this->line('Missing or invalid: '.$counts['missing_or_invalid']);
        $this->line('Failed: '.$counts['failed']);

        return $counts['failed'] === 0 ? self::SUCCESS : self::FAILURE;
    }
}
