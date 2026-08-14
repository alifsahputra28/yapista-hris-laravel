@extends('layouts.admin')

@section('title', 'Dashboard Panitia Scanner | YAPISTA HRIS')

@section('content')
    <x-page-header
        title="Dashboard Panitia"
        subtitle="Pilih kegiatan aktif untuk memindai QR Code atau memantau daftar hadir."
        :breadcrumbs="[['label' => 'Scanner'], ['label' => 'Dashboard']]"
    >
        <x-slot:meta>
            <span class="text-muted small">{{ number_format($activeEvents->count()) }} kegiatan aktif</span>
        </x-slot:meta>
    </x-page-header>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Kegiatan Aktif</h5>
        </div>
        <div class="card-body">
            @if ($activeEvents->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 scanner-event-table">
                        <thead>
                            <tr>
                                <th>Kegiatan</th>
                                <th>Jadwal</th>
                                <th>Lokasi</th>
                                <th>Peserta</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($activeEvents as $event)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $event->name }}</div>
                                        <div class="data-meta">Status: {{ $event->status_label }}</div>
                                    </td>
                                    <td>
                                        <div>{{ $event->event_date?->locale('id')->translatedFormat('d M Y') }}</div>
                                        <div class="data-meta">
                                            {{ $event->start_time?->format('H:i') ?? '-' }}
                                            @if ($event->end_time)
                                                - {{ $event->end_time->format('H:i') }}
                                            @endif
                                        </div>
                                    </td>
                                    <td>{{ $event->location ?? '-' }}</td>
                                    <td>
                                        <span class="badge bg-light-primary text-primary">{{ $event->attendances_count }}/{{ $event->participants_count }} hadir</span>
                                    </td>
                                    <td class="text-end">
                                        <div class="table-actions">
                                            <a href="{{ route('events.scanner', $event) }}" class="btn btn-sm btn-primary">
                                                <i class="ti ti-qrcode"></i>
                                                Scan QR Code
                                            </a>
                                            <a href="{{ route('events.attendances.index', $event) }}" class="btn btn-sm btn-light-secondary">
                                                <i class="ti ti-list-check"></i>
                                                Daftar Hadir
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-state">
                    <div class="avtar avtar-l bg-light-secondary text-secondary">
                        <i class="ti ti-calendar-off f-28"></i>
                    </div>
                    <h5 class="mb-1">Belum ada kegiatan aktif.</h5>
                    <p class="text-muted mb-0">Kegiatan akan muncul di sini setelah Admin/HR mengaktifkannya.</p>
                </div>
            @endif
        </div>
    </div>
@endsection
