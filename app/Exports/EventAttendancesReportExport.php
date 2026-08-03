<?php

namespace App\Exports;

use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\EventParticipant;
use App\Support\Reports\SimpleXlsxWriter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EventAttendancesReportExport
{
    /**
     * @param  Builder<EventParticipant>  $participantsQuery
     * @param  Collection<int, EventAttendance>  $attendanceMap
     */
    public function __construct(
        private readonly Event $event,
        private readonly Builder $participantsQuery,
        private readonly Collection $attendanceMap
    ) {}

    public function download(string $filename): StreamedResponse
    {
        $participants = (clone $this->participantsQuery)
            ->with(['employee.institution', 'employee.position'])
            ->get();

        $rows = $participants->values()->map(function (EventParticipant $participant, int $index): array {
            $employee = $participant->employee;
            $attendance = $employee ? $this->attendanceMap->get($employee->id) : null;

            return [
                $index + 1,
                $employee?->full_name ?: '-',
                $employee?->employee_number ?: 'Belum diisi',
                $employee?->institution?->name ?: '-',
                $employee?->position?->name ?: '-',
                $attendance ? 'Hadir' : 'Belum Hadir',
                $attendance?->scanned_at?->format('d/m/Y H:i:s') ?: '',
                $attendance?->scan_method_label ?: '',
                $attendance?->scanner?->name ?: '',
            ];
        });

        return SimpleXlsxWriter::download($filename, [
            'No',
            'Nama Pegawai',
            'NUP / Nomor Pegawai',
            'Unit Kerja',
            'Jabatan',
            'Status Hadir',
            'Waktu Scan',
            'Metode Scan',
            'Petugas Scan',
        ], $rows, $this->event->name);
    }
}
