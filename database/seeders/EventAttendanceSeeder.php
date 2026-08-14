<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\EmployeeQrToken;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\EventParticipant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class EventAttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $panitia = User::where('email', 'panitia@yapista.test')->first();

        $this->seedAttendance('Rapat Koordinasi Yayasan', [
            ['login_email' => 'pegawai@yapista.test', 'method' => 'qr', 'minutes' => 5],
            ['login_email' => 'budi.santoso@yapista.test', 'method' => 'qr', 'minutes' => 12],
            ['login_email' => 'dewi.lestari@yapista.test', 'method' => 'manual', 'minutes' => 18, 'note' => 'Input manual karena QR Code sulit terbaca.'],
        ], $panitia?->id);

        $this->seedAttendance('Halal Bihalal YAPISTA', [
            ['login_email' => 'pegawai@yapista.test', 'method' => 'qr', 'minutes' => 3],
            ['login_email' => 'budi.santoso@yapista.test', 'method' => 'qr', 'minutes' => 6],
            ['login_email' => 'andi.pratama@yapista.test', 'method' => 'qr', 'minutes' => 8],
            ['login_email' => 'dewi.lestari@yapista.test', 'method' => 'manual', 'minutes' => 10, 'note' => 'Input manual saat antrean scan penuh.'],
            ['login_email' => 'fajar.ramadhan@yapista.test', 'method' => 'qr', 'minutes' => 13],
            ['login_email' => 'rahmat.hidayat@yapista.test', 'method' => 'qr', 'minutes' => 17],
        ], $panitia?->id);
    }

    /**
     * @param  array<int, array{login_email: string, method: string, minutes: int, note?: string}>  $attendances
     */
    private function seedAttendance(string $eventName, array $attendances, ?int $scannerId): void
    {
        $event = Event::where('name', $eventName)->first();

        if (! $event) {
            return;
        }

        $baseTime = Carbon::parse($event->event_date->format('Y-m-d').' '.($event->start_time?->format('H:i:s') ?? '08:00:00'));

        foreach ($attendances as $attendance) {
            $employee = Employee::query()
                ->whereHas('user', fn ($query) => $query->where('email', $attendance['login_email']))
                ->first();

            if (! $employee || ! $employee->isVerified()) {
                continue;
            }

            $isParticipant = EventParticipant::where('event_id', $event->id)
                ->where('employee_id', $employee->id)
                ->where('participant_status', '!=', 'cancelled')
                ->exists();

            if (! $isParticipant) {
                continue;
            }

            $qrToken = EmployeeQrToken::query()
                ->where('employee_id', $employee->id)
                ->where('is_active', true)
                ->whereNull('revoked_at')
                ->first();

            EventAttendance::updateOrCreate(
                [
                    'event_id' => $event->id,
                    'employee_id' => $employee->id,
                ],
                [
                    'qr_token_id' => $attendance['method'] === 'manual' ? null : $qrToken?->id,
                    'scanned_by' => $scannerId,
                    'scanned_at' => $baseTime->copy()->addMinutes($attendance['minutes']),
                    'attendance_status' => $attendance['method'] === 'manual' ? 'manual' : 'present',
                    'scan_method' => $attendance['method'],
                    'note' => $attendance['note'] ?? null,
                ],
            );
        }
    }
}
