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
            ['email' => 'ahmad.fauzi@yapista.test', 'method' => 'barcode', 'minutes' => 5],
            ['email' => 'budi.santoso@yapista.test', 'method' => 'barcode', 'minutes' => 12],
            ['email' => 'dewi.lestari@yapista.test', 'method' => 'manual', 'minutes' => 18, 'note' => 'Input manual karena barcode sulit terbaca.'],
        ], $panitia?->id);

        $this->seedAttendance('Halal Bihalal YAPISTA', [
            ['email' => 'ahmad.fauzi@yapista.test', 'method' => 'barcode', 'minutes' => 3],
            ['email' => 'budi.santoso@yapista.test', 'method' => 'barcode', 'minutes' => 6],
            ['email' => 'andi.pratama@yapista.test', 'method' => 'barcode', 'minutes' => 8],
            ['email' => 'dewi.lestari@yapista.test', 'method' => 'manual', 'minutes' => 10, 'note' => 'Input manual saat antrean scan penuh.'],
            ['email' => 'fajar.ramadhan@yapista.test', 'method' => 'barcode', 'minutes' => 13],
            ['email' => 'rahmat.hidayat@yapista.test', 'method' => 'barcode', 'minutes' => 17],
        ], $panitia?->id);
    }

    /**
     * @param  array<int, array{email: string, method: string, minutes: int, note?: string}>  $attendances
     */
    private function seedAttendance(string $eventName, array $attendances, ?int $scannerId): void
    {
        $event = Event::where('name', $eventName)->first();

        if (! $event) {
            return;
        }

        $baseTime = Carbon::parse($event->event_date->format('Y-m-d').' '.($event->start_time?->format('H:i:s') ?? '08:00:00'));

        foreach ($attendances as $attendance) {
            $employee = Employee::where('email', $attendance['email'])->first();

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
                    'qr_token_id' => $qrToken?->id,
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
