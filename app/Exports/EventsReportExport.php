<?php

namespace App\Exports;

use App\Models\Event;
use App\Services\EventAttendanceSummaryService;
use App\Support\Reports\SimpleXlsxWriter;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EventsReportExport
{
    /**
     * @param  Builder<Event>  $query
     */
    public function __construct(
        private readonly Builder $query,
        private readonly EventAttendanceSummaryService $attendanceSummaryService,
    ) {}

    public function download(string $filename): StreamedResponse
    {
        $events = (clone $this->query)
            ->with('creator')
            ->get();

        $rows = $events->values()->map(function (Event $event, int $index): array {
            $summary = $this->attendanceSummaryService->summarizeLoaded($event);

            return [
                $index + 1,
                $event->name,
                $event->event_date?->format('d/m/Y') ?: '-',
                $this->timeRange($event),
                $event->location ?: '-',
                $event->target_type_label,
                $event->status_label,
                $summary['totalParticipants'],
                $summary['attendedCount'],
                $summary['absentCount'],
                $summary['attendancePercentage'].'%',
                $event->creator?->name ?: '-',
            ];
        });

        return SimpleXlsxWriter::download($filename, [
            'No',
            'Nama Kegiatan',
            'Tanggal',
            'Waktu',
            'Lokasi',
            'Target Peserta',
            'Status',
            'Total Peserta Aktif',
            'Total Hadir',
            'Total Belum Hadir',
            'Persentase Kehadiran',
            'Dibuat Oleh',
        ], $rows, 'Laporan Kegiatan');
    }

    private function timeRange(Event $event): string
    {
        $start = $event->start_time?->format('H:i');
        $end = $event->end_time?->format('H:i');

        if ($start && $end) {
            return "{$start} - {$end}";
        }

        return $start ?: ($end ?: '-');
    }
}
