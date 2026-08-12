@extends('layouts.admin')

@section('title', 'Laporan Kegiatan')

@section('content')
@php
    $statusBadges = [
        'draft' => ['label' => 'Draft', 'class' => 'bg-light-secondary text-secondary'],
        'active' => ['label' => 'Aktif', 'class' => 'bg-light-success text-success'],
        'closed' => ['label' => 'Ditutup', 'class' => 'bg-light-primary text-primary'],
        'cancelled' => ['label' => 'Dibatalkan', 'class' => 'bg-light-danger text-danger'],
    ];
    $advancedFilterCount = request()->filled('target_type') ? 1 : 0;
    $hasActiveFilters = collect(['search', 'status', 'target_type', 'date_from', 'date_to'])->contains(fn ($key) => request()->filled($key));
    $activeFilterCount = collect(['status', 'target_type', 'date_from', 'date_to'])->filter(fn ($key) => request()->filled($key))->count();
@endphp

<x-page-header title="Laporan Kegiatan" subtitle="Filter dan ekspor rekap kegiatan yayasan." :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Laporan Kegiatan']]">
    <x-slot:actions><a href="{{ route('reports.events.export', request()->query()) }}" class="btn btn-primary"><i class="ti ti-file-spreadsheet" aria-hidden="true"></i> Export Excel</a></x-slot:actions>
</x-page-header>

<div class="metric-strip" aria-label="Ringkasan laporan kegiatan">
    <div class="metric-item"><div class="metric-label">Total Kegiatan</div><div class="metric-value">{{ number_format($totalEvents) }}</div></div>
    <div class="metric-item"><div class="metric-label">Kegiatan Aktif</div><div class="metric-value">{{ number_format($activeEvents) }}</div></div>
    <div class="metric-item"><div class="metric-label">Kegiatan Ditutup</div><div class="metric-value">{{ number_format($closedEvents) }}</div></div>
    <div class="metric-item"><div class="metric-label">Rata-rata Kehadiran</div><div class="metric-value">{{ $averageAttendance }}%</div></div>
</div>

<div class="card filter-card mb-4">
    <div class="card-body">
        <form id="event-report-filter-form" action="{{ route('reports.events') }}" method="GET">
            <div class="row g-3 align-items-end">
                <div class="col-lg-5">
                    <label for="search" class="form-label">Cari Kegiatan</label>
                    <div class="filter-search-wrap"><i class="ti ti-search" aria-hidden="true"></i><input type="search" name="search" id="search" class="form-control" value="{{ request('search') }}" placeholder="Cari nama kegiatan atau lokasi..." aria-label="Cari kegiatan dalam laporan"></div>
                </div>
                <div class="col-12 d-lg-none"><button class="btn btn-light-primary w-100" type="button" data-bs-toggle="collapse" data-bs-target=".event-report-mobile-filter" aria-expanded="{{ $activeFilterCount ? 'true' : 'false' }}"><i class="ti ti-adjustments-horizontal" aria-hidden="true"></i> Filter @if ($activeFilterCount)({{ $activeFilterCount }})@endif</button></div>
                <div class="col-md-6 col-lg-2 collapse d-lg-block event-report-mobile-filter {{ $activeFilterCount ? 'show' : '' }}">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">Semua</option>
                        @foreach ($eventStatuses as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-lg-2 collapse d-lg-block event-report-mobile-filter {{ $activeFilterCount ? 'show' : '' }}">
                    <label for="date_from" class="form-label">Dari Tanggal</label>
                    <input type="date" name="date_from" id="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-6 col-lg-2 collapse d-lg-block event-report-mobile-filter {{ $activeFilterCount ? 'show' : '' }}">
                    <label for="date_to" class="form-label">Sampai Tanggal</label>
                    <input type="date" name="date_to" id="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-lg-1 filter-primary-actions collapse d-lg-block event-report-mobile-filter {{ $activeFilterCount ? 'show' : '' }}"><button type="submit" class="btn btn-primary w-100" title="Terapkan Filter"><i class="ti ti-filter" aria-hidden="true"></i><span class="d-lg-none">Terapkan Filter</span></button></div>
            </div>
        </form>
        <div class="filter-secondary-row collapse d-lg-flex event-report-mobile-filter {{ $activeFilterCount ? 'show' : '' }}"><button class="btn btn-link filter-advanced-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#event-report-advanced-filter" aria-expanded="{{ $advancedFilterCount ? 'true' : 'false' }}" aria-controls="event-report-advanced-filter"><i class="ti ti-adjustments-horizontal" aria-hidden="true"></i>Filter Lanjutan @if ($advancedFilterCount)<span class="badge bg-light-primary text-primary">{{ $advancedFilterCount }}</span>@endif<i class="ti ti-chevron-down" aria-hidden="true"></i></button>@if ($hasActiveFilters)<a href="{{ route('reports.events') }}" class="btn btn-sm btn-link text-muted">Reset semua</a>@endif</div>
        <div id="event-report-advanced-filter" class="collapse {{ $advancedFilterCount ? 'show' : '' }}"><div class="filter-advanced-panel"><div class="row g-3"><div class="col-md-6 col-lg-4"><label for="target_type" class="form-label">Target Peserta</label><select name="target_type" id="target_type" class="form-select" form="event-report-filter-form"><option value="">Semua target</option>@foreach ($targetTypes as $value => $label)<option value="{{ $value }}" @selected(request('target_type') === $value)>{{ $label }}</option>@endforeach</select></div></div></div></div>
        @if ($hasActiveFilters)<div class="active-filter-summary" aria-label="Filter aktif"><span class="active-filter-label">Filter aktif:</span>
            @if (request('status'))<x-active-filter-chip label="Status" :value="$eventStatuses[request('status')] ?? request('status')" :url="route('reports.events', request()->except('status', 'page'))" />@endif
            @if (request('date_from'))<x-active-filter-chip label="Mulai" :value="request('date_from')" :url="route('reports.events', request()->except('date_from', 'page'))" />@endif
            @if (request('date_to'))<x-active-filter-chip label="Sampai" :value="request('date_to')" :url="route('reports.events', request()->except('date_to', 'page'))" />@endif
            @if (request('target_type'))<x-active-filter-chip label="Target" :value="$targetTypes[request('target_type')] ?? request('target_type')" :url="route('reports.events', request()->except('target_type', 'page'))" />@endif
        </div>@endif
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        @if ($events->count())
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 70px;">No</th>
                            <th>Kegiatan</th>
                            <th>Waktu & Lokasi</th>
                            <th>Target</th>
                            <th>Status</th>
                            <th>Rekap Kehadiran</th>
                            <th>Dibuat Oleh</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($events as $event)
                            @php
                                $status = $statusBadges[$event->status] ?? ['label' => $event->status ?: '-', 'class' => 'bg-light-secondary text-secondary'];
                                $totalParticipants = (int) $event->active_participants_count;
                                $totalAttended = (int) $event->active_attendances_count;
                                $totalAbsent = max($totalParticipants - $totalAttended, 0);
                                $percentage = $totalParticipants > 0 ? round(($totalAttended / $totalParticipants) * 100, 1) : 0;
                            @endphp
                            <tr>
                                <td>{{ $events->firstItem() + $loop->index }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $event->name }}</div>
                                    <div class="data-meta">{{ $event->description ? \Illuminate\Support\Str::limit($event->description, 80) : 'Tidak ada deskripsi' }}</div>
                                </td>
                                <td>
                                    <div>{{ $event->event_date?->format('d M Y') ?: '-' }}</div>
                                    <div class="data-meta">
                                        {{ $event->start_time?->format('H:i') ?: '-' }}
                                        @if ($event->end_time)
                                            - {{ $event->end_time->format('H:i') }}
                                        @endif
                                    </div>
                                    <div class="data-meta">{{ $event->location ?: '-' }}</div>
                                </td>
                                <td>{{ $event->target_type_label }}</td>
                                <td><span class="badge {{ $status['class'] }}">{{ $status['label'] }}</span></td>
                                <td>
                                    <div class="fw-semibold">{{ $percentage }}%</div>
                                    <div class="data-meta">{{ $totalAttended }} hadir / {{ $totalParticipants }} peserta</div>
                                    <div class="data-meta">{{ $totalAbsent }} belum hadir</div>
                                </td>
                                <td>{{ $event->creator?->name ?: '-' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('reports.events.attendances', $event) }}" class="btn btn-sm btn-light">
                                        <i class="ti ti-list-check me-1"></i> Detail Kehadiran
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">
                <div class="avtar bg-light-secondary text-secondary"><i class="ti ti-calendar-off"></i></div>
                <h6 class="mb-1">{{ $hasActiveFilters ? 'Tidak ada kegiatan yang sesuai dengan filter.' : 'Belum ada data kegiatan' }}</h6>
                <p class="text-muted {{ $hasActiveFilters ? 'mb-3' : 'mb-0' }}">{{ $hasActiveFilters ? 'Ubah atau reset filter untuk melihat kegiatan lainnya.' : 'Data kegiatan akan muncul setelah tersedia.' }}</p>
                @if ($hasActiveFilters)<a href="{{ route('reports.events') }}" class="btn btn-light-primary"><i class="ti ti-filter-off"></i> Reset Filter</a>@endif
            </div>
        @endif
    </div>
    @if ($events->hasPages())
        <div class="card-footer">
            {{ $events->links() }}
        </div>
    @endif
</div>
@endsection
