@extends('layouts.admin')

@section('title', 'Laporan Kehadiran Kegiatan')

@section('content')
@php
    $eventStatusBadges = [
        'draft' => ['label' => 'Draft', 'class' => 'bg-light-secondary text-secondary'],
        'active' => ['label' => 'Aktif', 'class' => 'bg-light-success text-success'],
        'closed' => ['label' => 'Ditutup', 'class' => 'bg-light-primary text-primary'],
        'cancelled' => ['label' => 'Dibatalkan', 'class' => 'bg-light-danger text-danger'],
    ];
    $eventStatus = $eventStatusBadges[$event->status] ?? ['label' => $event->status ?: '-', 'class' => 'bg-light-secondary text-secondary'];
    $scanMethods = ['qr' => 'QR Code', 'manual' => 'Manual', 'barcode' => 'Barcode (Histori)'];
    $activeInstitution = $institutions->firstWhere('id', (int) request('institution_id'))?->name;
    $activePosition = $positions->firstWhere('id', (int) request('position_id'))?->name;
    $advancedFilterCount = collect(['position_id', 'scan_method'])->filter(fn ($key) => request()->filled($key))->count();
    $hasActiveFilters = collect(['search', 'institution_id', 'position_id', 'attendance_status', 'scan_method'])->contains(fn ($key) => request()->filled($key));
    $activeFilterCount = collect(['institution_id', 'position_id', 'attendance_status', 'scan_method'])->filter(fn ($key) => request()->filled($key))->count();
@endphp

<x-page-header
    title="Laporan Kehadiran"
    :subtitle="$event->name"
    :badge-label="$eventStatus['label']"
    :badge-class="$eventStatus['class']"
    :breadcrumbs="[['label' => 'Laporan Kegiatan', 'url' => route('reports.events')], ['label' => 'Kehadiran']]"
>
    <x-slot:meta><span>{{ $event->event_date?->locale('id')->translatedFormat('d M Y') ?: '-' }}</span><span aria-hidden="true">&bull;</span><span>{{ $event->location ?: 'Lokasi belum diisi' }}</span></x-slot:meta>
    <x-slot:actions><a href="{{ route('reports.events') }}" class="btn btn-light-secondary"><i class="ti ti-arrow-left" aria-hidden="true"></i> Kembali</a><a href="{{ route('reports.events.attendances.export', array_merge(request()->query(), ['event' => $event->id])) }}" class="btn btn-primary"><i class="ti ti-file-export" aria-hidden="true"></i> Export Excel</a></x-slot:actions>
</x-page-header>

<div class="metric-strip" aria-label="Ringkasan kehadiran">
    <div class="metric-item"><div class="metric-label">Total Peserta</div><div class="metric-value">{{ number_format($totalParticipants) }}</div></div>
    <div class="metric-item"><div class="metric-label">Hadir</div><div class="metric-value">{{ number_format($attendedCount) }}</div></div>
    <div class="metric-item"><div class="metric-label">Belum Hadir</div><div class="metric-value">{{ number_format($absentCount) }}</div></div>
    <div class="metric-item"><div class="metric-label">Persentase</div><div class="metric-value">{{ $attendancePercentage }}%</div></div>
</div>

<div class="card filter-card mb-4">
    <div class="card-body">
        <form id="attendance-report-filter-form" action="{{ route('reports.events.attendances', $event) }}" method="GET">
            <div class="row g-3 align-items-end">
                <div class="col-lg-5">
                    <label for="search" class="form-label">Cari Peserta</label>
                    <div class="filter-search-wrap"><i class="ti ti-search" aria-hidden="true"></i><input type="search" name="search" id="search" class="form-control" value="{{ request('search') }}" placeholder="Cari nama atau NUP..." aria-label="Cari peserta pada laporan kehadiran"></div>
                </div>
                <div class="col-12 d-lg-none"><button class="btn btn-light-primary w-100" type="button" data-bs-toggle="collapse" data-bs-target=".attendance-report-mobile-filter" aria-expanded="{{ $activeFilterCount ? 'true' : 'false' }}"><i class="ti ti-adjustments-horizontal" aria-hidden="true"></i> Filter @if ($activeFilterCount)({{ $activeFilterCount }})@endif</button></div>
                <div class="col-md-6 col-lg-3 collapse d-lg-block attendance-report-mobile-filter {{ $activeFilterCount ? 'show' : '' }}">
                    <label for="institution_id" class="form-label">Unit Kerja</label>
                    <select name="institution_id" id="institution_id" class="form-select">
                        <option value="">Semua unit</option>
                        @foreach ($institutions as $institution)
                            <option value="{{ $institution->id }}" @selected((string) request('institution_id') === (string) $institution->id)>
                                {{ $institution->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-lg-3 collapse d-lg-block attendance-report-mobile-filter {{ $activeFilterCount ? 'show' : '' }}">
                    <label for="attendance_status" class="form-label">Status Hadir</label>
                    <select name="attendance_status" id="attendance_status" class="form-select">
                        <option value="">Semua</option>
                        <option value="present" @selected(request('attendance_status') === 'present')>Hadir</option>
                        <option value="absent" @selected(request('attendance_status') === 'absent')>Belum Hadir</option>
                    </select>
                </div>
                <div class="col-lg-1 filter-primary-actions collapse d-lg-block attendance-report-mobile-filter {{ $activeFilterCount ? 'show' : '' }}"><button type="submit" class="btn btn-primary w-100" title="Terapkan Filter"><i class="ti ti-filter" aria-hidden="true"></i><span class="d-lg-none">Terapkan Filter</span></button></div>
            </div>
        </form>
        <div class="filter-secondary-row collapse d-lg-flex attendance-report-mobile-filter {{ $activeFilterCount ? 'show' : '' }}"><button class="btn btn-link filter-advanced-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#attendance-report-advanced-filter" aria-expanded="{{ $advancedFilterCount ? 'true' : 'false' }}" aria-controls="attendance-report-advanced-filter"><i class="ti ti-adjustments-horizontal" aria-hidden="true"></i>Filter Lanjutan @if ($advancedFilterCount)<span class="badge bg-light-primary text-primary">{{ $advancedFilterCount }}</span>@endif<i class="ti ti-chevron-down" aria-hidden="true"></i></button>@if ($hasActiveFilters)<a href="{{ route('reports.events.attendances', $event) }}" class="btn btn-sm btn-link text-muted">Reset semua</a>@endif</div>
        <div id="attendance-report-advanced-filter" class="collapse {{ $advancedFilterCount ? 'show' : '' }}"><div class="filter-advanced-panel"><div class="row g-3">
            <div class="col-md-6"><label for="position_id" class="form-label">Jabatan</label><select name="position_id" id="position_id" class="form-select" form="attendance-report-filter-form"><option value="">Semua jabatan</option>@foreach ($positions as $position)<option value="{{ $position->id }}" @selected((string) request('position_id') === (string) $position->id)>{{ $position->name }}@if ($position->institution) - {{ $position->institution->name }}@endif</option>@endforeach</select></div>
            <div class="col-md-6"><label for="scan_method" class="form-label">Metode Kehadiran</label><select name="scan_method" id="scan_method" class="form-select" form="attendance-report-filter-form"><option value="">Semua metode</option>@foreach ($scanMethods as $value => $label)<option value="{{ $value }}" @selected(request('scan_method') === $value)>{{ $label }}</option>@endforeach</select></div>
        </div></div></div>
        @if ($hasActiveFilters)<div class="active-filter-summary" aria-label="Filter aktif"><span class="active-filter-label">Filter aktif:</span>
            @if ($activeInstitution)<x-active-filter-chip label="Unit" :value="$activeInstitution" :url="route('reports.events.attendances', array_merge(['event' => $event->id], request()->except('institution_id', 'page')))" />@endif
            @if (request('attendance_status'))<x-active-filter-chip label="Kehadiran" :value="request('attendance_status') === 'present' ? 'Hadir' : 'Belum Hadir'" :url="route('reports.events.attendances', array_merge(['event' => $event->id], request()->except('attendance_status', 'page')))" />@endif
            @if ($activePosition)<x-active-filter-chip label="Jabatan" :value="$activePosition" :url="route('reports.events.attendances', array_merge(['event' => $event->id], request()->except('position_id', 'page')))" />@endif
            @if (request('scan_method'))<x-active-filter-chip label="Metode" :value="$scanMethods[request('scan_method')] ?? request('scan_method')" :url="route('reports.events.attendances', array_merge(['event' => $event->id], request()->except('scan_method', 'page')))" />@endif
        </div>@endif
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        @if ($participants->count())
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 70px;">No</th>
                            <th>Pegawai</th>
                            <th>Unit & Jabatan</th>
                            <th>Status Hadir</th>
                            <th>Waktu Scan</th>
                            <th>Metode</th>
                            <th>Petugas Scan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($participants as $participant)
                            @php
                                $employee = $participant->employee;
                                $attendance = $employee ? $attendanceMap->get($employee->id) : null;
                            @endphp
                            <tr>
                                <td>{{ $participants->firstItem() + $loop->index }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $employee?->full_name ?: '-' }}</div>
                                    <div class="data-meta">NUP / Nomor Pegawai: {{ $employee?->employee_number ?: 'Belum dibuat' }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $employee?->institution?->name ?: '-' }}</div>
                                    <div class="data-meta">{{ $employee?->position?->name ?: '-' }}</div>
                                </td>
                                <td>
                                    @if ($attendance)
                                        <span class="badge bg-light-success text-success">Hadir</span>
                                    @else
                                        <span class="badge bg-light-secondary text-secondary">Belum Hadir</span>
                                    @endif
                                </td>
                                <td>{{ $attendance?->scanned_at?->locale('id')->translatedFormat('d M Y H:i:s') ?: '-' }}</td>
                                <td>
                                    @if ($attendance)
                                        <span class="badge {{ $attendance->scan_method === 'manual' ? 'bg-light-warning text-warning' : 'bg-light-primary text-primary' }}">
                                            {{ $attendance->scan_method_label }}
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $attendance?->scanner?->name ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">
                <div class="avtar bg-light-secondary text-secondary"><i class="ti ti-clipboard-x"></i></div>
                <h6 class="mb-1">{{ $hasActiveFilters ? 'Tidak ada peserta yang sesuai dengan filter.' : 'Belum ada data peserta' }}</h6>
                <p class="text-muted {{ $hasActiveFilters ? 'mb-3' : 'mb-0' }}">{{ $hasActiveFilters ? 'Ubah atau reset filter untuk melihat peserta lainnya.' : 'Pastikan peserta kegiatan sudah dibuat.' }}</p>
                @if ($hasActiveFilters)<a href="{{ route('reports.events.attendances', $event) }}" class="btn btn-light-primary"><i class="ti ti-filter-off"></i> Reset Filter</a>@endif
            </div>
        @endif
    </div>
    @if ($participants->hasPages())
        <div class="card-footer">
            {{ $participants->links() }}
        </div>
    @endif
</div>
@endsection
