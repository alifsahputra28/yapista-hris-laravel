<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\EventAttendance;
use App\Services\EmployeeQrTokenService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepairDatabaseIntegrity extends Command
{
    protected $signature = 'employees:repair-integrity {--dry-run : Audit without changing data} {--commit : Apply the safe, idempotent repairs}';

    protected $description = 'Audit or repair legacy employee verification, QR eligibility, and manual attendance references';

    public function handle(EmployeeQrTokenService $qrTokenService): int
    {
        if ($this->option('dry-run') && $this->option('commit')) {
            $this->error('Gunakan salah satu opsi --dry-run atau --commit.');

            return self::INVALID;
        }

        $commit = (bool) $this->option('commit');
        $summary = $this->audit();

        $this->components->info($commit ? 'Applying integrity repairs.' : 'Dry run only. No data will be changed.');
        $this->table(['Check', 'Count'], [
            ['Valid NUP not verified', $summary['valid_nup_not_verified']],
            ['Verified without verified_at', $summary['verified_without_timestamp']],
            ['Eligible employee without active QR', $summary['eligible_without_qr']],
            ['Ineligible employee with active QR', $summary['ineligible_with_qr']],
            ['Manual attendance linked to QR', $summary['manual_with_qr']],
        ]);

        if (! $commit) {
            $this->line('Run again with --commit after reviewing this summary.');

            return self::SUCCESS;
        }

        $repaired = [
            'verified' => 0,
            'verified_at' => 0,
            'qr_created' => 0,
            'qr_revoked' => 0,
            'manual_qr_cleared' => 0,
        ];

        Employee::query()->orderBy('id')->chunkById(100, function ($employees) use ($qrTokenService, &$repaired): void {
            foreach ($employees as $candidate) {
                DB::transaction(function () use ($candidate, $qrTokenService, &$repaired): void {
                    $employee = Employee::query()->lockForUpdate()->findOrFail($candidate->id);

                    if ($employee->hasValidEmployeeNumber() && ! $employee->isVerified()) {
                        $employee->forceFill([
                            'verification_status' => 'verified',
                            'verified_at' => now(),
                        ])->save();
                        $repaired['verified']++;
                    } elseif ($employee->isVerified() && $employee->verified_at === null) {
                        $employee->forceFill([
                            'verified_at' => $employee->created_at ?? now(),
                        ])->save();
                        $repaired['verified_at']++;
                    }

                    if ($employee->isEligibleForIdCard()) {
                        if (! $employee->activeQrToken()->exists()) {
                            $qrTokenService->generate($employee);
                            $repaired['qr_created']++;
                        }
                    } elseif ($employee->activeQrToken()->exists()) {
                        $qrTokenService->revoke($employee);
                        $repaired['qr_revoked']++;
                    }
                });
            }
        });

        $repaired['manual_qr_cleared'] = DB::transaction(fn (): int => EventAttendance::query()
            ->where('scan_method', 'manual')
            ->whereNotNull('qr_token_id')
            ->update(['qr_token_id' => null]));

        $this->table(['Repair', 'Updated'], collect($repaired)
            ->map(fn (int $count, string $name): array => [$name, $count])
            ->values()
            ->all());

        $remaining = array_sum($this->audit());
        if ($remaining > 0) {
            $this->error("Verification found {$remaining} remaining integrity issue(s).");

            return self::FAILURE;
        }

        $this->components->info('Integrity repair completed and verified.');

        return self::SUCCESS;
    }

    /** @return array<string, int> */
    private function audit(): array
    {
        $employees = Employee::query()->with('activeQrToken')->get();

        return [
            'valid_nup_not_verified' => $employees
                ->filter(fn (Employee $employee): bool => $employee->hasValidEmployeeNumber() && ! $employee->isVerified())
                ->count(),
            'verified_without_timestamp' => $employees
                ->filter(fn (Employee $employee): bool => $employee->isVerified() && $employee->verified_at === null)
                ->count(),
            'eligible_without_qr' => $employees
                ->filter(fn (Employee $employee): bool => $employee->isEligibleForIdCard() && $employee->activeQrToken === null)
                ->count(),
            'ineligible_with_qr' => $employees
                ->filter(fn (Employee $employee): bool => ! $employee->isEligibleForIdCard() && $employee->activeQrToken !== null)
                ->count(),
            'manual_with_qr' => EventAttendance::query()
                ->where('scan_method', 'manual')
                ->whereNotNull('qr_token_id')
                ->count(),
        ];
    }
}
