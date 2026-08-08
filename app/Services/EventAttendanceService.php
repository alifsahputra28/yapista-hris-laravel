<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeQrToken;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\EventParticipant;
use App\Models\User;
use App\Support\Attendances\AttendanceResult;
use Illuminate\Database\UniqueConstraintViolationException;

class EventAttendanceService
{
    public function recordQrAttendance(
        Event $event,
        Employee $employee,
        User $scanner,
        EmployeeQrToken $qrToken,
    ): AttendanceResult {
        $activeToken = EmployeeQrToken::query()
            ->whereKey($qrToken->id)
            ->where('employee_id', $employee->id)
            ->where('is_active', true)
            ->whereNull('revoked_at')
            ->first();

        if (! $activeToken) {
            return AttendanceResult::rejected('QR Code tidak valid atau sudah tidak aktif.');
        }

        return $this->record($event, $employee, $scanner, 'qr', null, $activeToken->id);
    }

    public function recordManualAttendance(
        Event $event,
        Employee $employee,
        User $scanner,
        ?string $note = null,
    ): AttendanceResult {
        return $this->record($event, $employee, $scanner, 'manual', $note, null);
    }

    public function inactiveEventMessage(Event $event): ?string
    {
        return match ($event->status) {
            'draft' => 'Kegiatan belum diaktifkan.',
            'closed' => 'Kegiatan sudah ditutup.',
            'cancelled' => 'Kegiatan telah dibatalkan.',
            'active' => null,
            default => 'Kegiatan tidak dapat menerima absensi.',
        };
    }

    private function record(
        Event $event,
        Employee $employee,
        User $scanner,
        string $scanMethod,
        ?string $note,
        ?int $qrTokenId,
    ): AttendanceResult {
        if ($message = $this->validationError($event, $employee)) {
            return AttendanceResult::rejected($message);
        }

        if ($existing = $this->findExistingAttendance($event, $employee)) {
            return $this->alreadyAttended($employee, $existing);
        }

        try {
            $attendance = $this->createAttendance([
                'event_id' => $event->id,
                'employee_id' => $employee->id,
                'qr_token_id' => $qrTokenId,
                'scanned_by' => $scanner->id,
                'scanned_at' => now(),
                'attendance_status' => 'present',
                'scan_method' => $scanMethod,
                'note' => $note,
            ]);
        } catch (UniqueConstraintViolationException $exception) {
            if (! $this->isEventEmployeeDuplicate($exception)) {
                throw $exception;
            }

            $existing = $this->findExistingAttendance($event, $employee);

            if (! $existing) {
                throw $exception;
            }

            return $this->alreadyAttended($employee, $existing);
        }

        return AttendanceResult::success($attendance);
    }

    private function validationError(Event $event, Employee $employee): ?string
    {
        if ($message = $this->inactiveEventMessage($event)) {
            return $message;
        }

        if (! $employee->isVerified()) {
            return 'Pegawai belum terverifikasi.';
        }

        if (! $employee->hasValidEmployeeNumber()) {
            return 'Pegawai belum memiliki NUP / Nomor Pegawai yang valid.';
        }

        if (in_array($employee->employment_status, ['nonaktif', 'resign'], true)) {
            return 'Status kepegawaian tidak memenuhi syarat untuk melakukan absensi.';
        }

        $participant = EventParticipant::query()
            ->where('event_id', $event->id)
            ->where('employee_id', $employee->id)
            ->first();

        if (! $participant) {
            return 'Pegawai tidak terdaftar sebagai peserta kegiatan.';
        }

        if ($participant->isCancelled()) {
            return 'Keikutsertaan pegawai pada kegiatan ini telah dibatalkan.';
        }

        return null;
    }

    protected function findExistingAttendance(Event $event, Employee $employee): ?EventAttendance
    {
        return EventAttendance::query()
            ->where('event_id', $event->id)
            ->where('employee_id', $employee->id)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function createAttendance(array $attributes): EventAttendance
    {
        return EventAttendance::create($attributes);
    }

    private function alreadyAttended(Employee $employee, EventAttendance $attendance): AttendanceResult
    {
        $time = $attendance->scanned_at
            ? $attendance->scanned_at->copy()->timezone('Asia/Jakarta')->format('H:i').' WIB'
            : 'waktu yang sudah tercatat';

        return AttendanceResult::alreadyAttended(
            $attendance,
            $employee->full_name.' sudah melakukan absensi pada '.$time.'.'
        );
    }

    private function isEventEmployeeDuplicate(UniqueConstraintViolationException $exception): bool
    {
        $index = strtolower((string) $exception->index);
        $columns = collect($exception->columns)
            ->map(fn (string $column): string => strtolower(str_contains($column, '.')
                ? substr($column, strrpos($column, '.') + 1)
                : $column))
            ->sort()
            ->values()
            ->all();

        if ($index === 'event_attendances_event_id_employee_id_unique') {
            return true;
        }

        if ($columns === ['employee_id', 'event_id']) {
            return true;
        }

        $message = strtolower($exception->getMessage());

        return str_contains($message, 'event_attendances_event_id_employee_id_unique')
            || (str_contains($message, 'event_attendances.event_id')
                && str_contains($message, 'event_attendances.employee_id'));
    }
}
