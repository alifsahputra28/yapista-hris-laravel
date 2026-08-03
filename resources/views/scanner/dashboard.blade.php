@extends('layouts.admin')

@section('title', 'Dashboard Panitia Scanner | YAPISTA HRIS')

@section('content')
    @php
        $activeEvents = class_exists(\App\Models\Event::class)
            ? \App\Models\Event::query()
                ->where('status', 'active')
                ->withCount(['participants', 'attendances'])
                ->orderBy('event_date')
                ->get()
            : collect();
    @endphp

    <div class="page-header">
        <div class="page-block">
            <div class="row align-items-center">
                <div class="col-md-12">
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item" aria-current="page">Scanner</li>
                        <li class="breadcrumb-item" aria-current="page">Dashboard</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card page-intro-card">
                <div class="card-body">
                    <h4 class="mb-2">Dashboard Panitia Scanner</h4>
                    <p class="mb-0 text-muted">Area kerja panitia untuk proses scan dan kehadiran kegiatan yayasan.</p>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card summary-card">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avtar avtar-s bg-light-success text-success">
                            <i class="ti ti-calendar-event f-20"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Kegiatan Aktif</div>
                            <h4 class="mb-0">{{ number_format($activeEvents->count()) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Kegiatan Aktif</h5>
        </div>
        <div class="card-body">
            @if ($activeEvents->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
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
                                        <div>{{ $event->event_date?->format('d M Y') }}</div>
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
                                                <i class="ti ti-barcode"></i>
                                                Scan Barcode
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
