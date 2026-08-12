@extends('layouts.admin')

@section('title', 'Laporan Pegawai')

@section('content')
@php
    $verificationBadges = [
        'draft' => ['label' => 'Draft', 'class' => 'bg-light-secondary text-secondary'],
        'submitted' => ['label' => 'Menunggu Verifikasi', 'class' => 'bg-light-primary text-primary'],
        'verified' => ['label' => 'Terverifikasi', 'class' => 'bg-light-success text-success'],
        'rejected' => ['label' => 'Ditolak', 'class' => 'bg-light-danger text-danger'],
    ];
    $employmentBadges = [
        'aktif' => ['label' => 'Aktif', 'class' => 'bg-light-success text-success'],
        'kontrak' => ['label' => 'Kontrak', 'class' => 'bg-light-primary text-primary'],
        'honorer' => ['label' => 'Honorer', 'class' => 'bg-light-warning text-warning'],
        'part_time' => ['label' => 'Part Time', 'class' => 'bg-light-info text-info'],
        'nonaktif' => ['label' => 'Nonaktif', 'class' => 'bg-light-danger text-danger'],
        'resign' => ['label' => 'Resign', 'class' => 'bg-light-secondary text-secondary'],
    ];
    $activeInstitution = $institutions->firstWhere('id', (int) request('institution_id'))?->name;
    $activePosition = $positions->firstWhere('id', (int) request('position_id'))?->name;
    $advancedFilterCount = collect(['employee_type', 'verification_status', 'registration_status', 'employee_number_status'])->filter(fn ($key) => request()->filled($key))->count();
    $hasActiveFilters = collect(['search', 'institution_id', 'position_id', 'employment_status', 'employee_type', 'verification_status', 'registration_status', 'employee_number_status'])->contains(fn ($key) => request()->filled($key));
    $activeFilterCount = collect(['institution_id', 'position_id', 'employment_status', 'employee_type', 'verification_status', 'registration_status', 'employee_number_status'])->filter(fn ($key) => request()->filled($key))->count();
@endphp

<x-page-header title="Laporan Pegawai" subtitle="Filter dan ekspor data pegawai." :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Laporan Pegawai']]">
    <x-slot:actions><a href="{{ route('reports.employees.export', request()->query()) }}" class="btn btn-primary"><i class="ti ti-file-spreadsheet" aria-hidden="true"></i> Export Excel</a></x-slot:actions>
</x-page-header>

<div class="metric-strip" aria-label="Ringkasan laporan pegawai">
    <div class="metric-item"><div class="metric-label">Total Pegawai</div><div class="metric-value">{{ number_format($totalEmployees) }}</div></div>
    <div class="metric-item"><div class="metric-label">Pegawai Aktif</div><div class="metric-value">{{ number_format($activeEmployees) }}</div></div>
    <div class="metric-item"><div class="metric-label">Akun Terhubung</div><div class="metric-value">{{ number_format($registeredEmployees) }}</div></div>
    <div class="metric-item"><div class="metric-label">Terverifikasi</div><div class="metric-value">{{ number_format($verifiedEmployees) }}</div></div>
</div>

<div class="card filter-card mb-4">
    <div class="card-body">
        <form id="employee-report-filter-form" action="{{ route('reports.employees') }}" method="GET">
            <div class="row g-3 align-items-end">
                <div class="col-lg-5">
                    <label for="search" class="form-label">Cari Pegawai</label>
                    <div class="filter-search-wrap"><i class="ti ti-search" aria-hidden="true"></i><input type="search" name="search" id="search" class="form-control" value="{{ request('search') }}" placeholder="Cari nama, email, HP, atau NUP..." aria-label="Cari pegawai dalam laporan"></div>
                </div>
                <div class="col-12 d-lg-none"><button class="btn btn-light-primary w-100" type="button" data-bs-toggle="collapse" data-bs-target=".employee-report-mobile-filter" aria-expanded="{{ $activeFilterCount ? 'true' : 'false' }}"><i class="ti ti-adjustments-horizontal" aria-hidden="true"></i> Filter @if ($activeFilterCount)({{ $activeFilterCount }})@endif</button></div>
                <div class="col-md-6 col-lg-2 collapse d-lg-block employee-report-mobile-filter {{ $activeFilterCount ? 'show' : '' }}">
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
                <div class="col-md-6 col-lg-2 collapse d-lg-block employee-report-mobile-filter {{ $activeFilterCount ? 'show' : '' }}">
                    <label for="position_id" class="form-label">Jabatan</label>
                    <select name="position_id" id="position_id" class="form-select">
                        <option value="">Semua jabatan</option>
                        @foreach ($positions as $position)
                            <option value="{{ $position->id }}" @selected((string) request('position_id') === (string) $position->id)>
                                {{ $position->name }}
                                @if ($position->institution)
                                    - {{ $position->institution->name }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-lg-2 collapse d-lg-block employee-report-mobile-filter {{ $activeFilterCount ? 'show' : '' }}">
                    <label for="employment_status" class="form-label">Status Kerja</label>
                    <select name="employment_status" id="employment_status" class="form-select">
                        <option value="">Semua</option>
                        @foreach ($employmentStatuses as $value => $label)
                            <option value="{{ $value }}" @selected(request('employment_status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-1 filter-primary-actions collapse d-lg-block employee-report-mobile-filter {{ $activeFilterCount ? 'show' : '' }}"><button type="submit" class="btn btn-primary w-100" title="Terapkan Filter"><i class="ti ti-filter" aria-hidden="true"></i><span class="d-lg-none">Terapkan Filter</span></button></div>
            </div>
        </form>
        <div class="filter-secondary-row collapse d-lg-flex employee-report-mobile-filter {{ $activeFilterCount ? 'show' : '' }}"><button class="btn btn-link filter-advanced-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#employee-report-advanced-filter" aria-expanded="{{ $advancedFilterCount ? 'true' : 'false' }}" aria-controls="employee-report-advanced-filter"><i class="ti ti-adjustments-horizontal" aria-hidden="true"></i>Filter Lanjutan @if ($advancedFilterCount)<span class="badge bg-light-primary text-primary">{{ $advancedFilterCount }}</span>@endif<i class="ti ti-chevron-down" aria-hidden="true"></i></button>@if ($hasActiveFilters)<a href="{{ route('reports.employees') }}" class="btn btn-sm btn-link text-muted">Reset semua</a>@endif</div>
        <div id="employee-report-advanced-filter" class="collapse {{ $advancedFilterCount ? 'show' : '' }}"><div class="filter-advanced-panel"><div class="row g-3">
            <div class="col-md-6 col-xl-3"><label for="employee_type" class="form-label">Jenis Pegawai</label><select name="employee_type" id="employee_type" class="form-select" form="employee-report-filter-form"><option value="">Semua jenis</option>@foreach ($employeeTypes as $value => $label)<option value="{{ $value }}" @selected(request('employee_type') === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-6 col-xl-3"><label for="verification_status" class="form-label">Status Verifikasi</label><select name="verification_status" id="verification_status" class="form-select" form="employee-report-filter-form"><option value="">Semua status</option>@foreach ($verificationStatuses as $value => $label)<option value="{{ $value }}" @selected(request('verification_status') === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-6 col-xl-3"><label for="registration_status" class="form-label">Registrasi Akun</label><select name="registration_status" id="registration_status" class="form-select" form="employee-report-filter-form"><option value="">Semua</option><option value="registered" @selected(request('registration_status') === 'registered')>Sudah Registrasi</option><option value="unregistered" @selected(request('registration_status') === 'unregistered')>Belum Registrasi</option></select></div>
            <div class="col-md-6 col-xl-3"><label for="employee_number_status" class="form-label">Ketersediaan NUP</label><select name="employee_number_status" id="employee_number_status" class="form-select" form="employee-report-filter-form"><option value="">Semua</option><option value="filled" @selected(request('employee_number_status') === 'filled')>Sudah Ada</option><option value="empty" @selected(request('employee_number_status') === 'empty')>Belum Ada</option></select></div>
        </div></div></div>
        @if ($hasActiveFilters)<div class="active-filter-summary" aria-label="Filter aktif"><span class="active-filter-label">Filter aktif:</span>
            @if ($activeInstitution)<x-active-filter-chip label="Unit" :value="$activeInstitution" :url="route('reports.employees', request()->except('institution_id', 'page'))" />@endif
            @if ($activePosition)<x-active-filter-chip label="Jabatan" :value="$activePosition" :url="route('reports.employees', request()->except('position_id', 'page'))" />@endif
            @if (request('employment_status'))<x-active-filter-chip label="Status" :value="$employmentStatuses[request('employment_status')] ?? request('employment_status')" :url="route('reports.employees', request()->except('employment_status', 'page'))" />@endif
            @if (request('employee_type'))<x-active-filter-chip label="Jenis" :value="$employeeTypes[request('employee_type')] ?? request('employee_type')" :url="route('reports.employees', request()->except('employee_type', 'page'))" />@endif
            @if (request('verification_status'))<x-active-filter-chip label="Verifikasi" :value="$verificationStatuses[request('verification_status')] ?? request('verification_status')" :url="route('reports.employees', request()->except('verification_status', 'page'))" />@endif
            @if (request('registration_status'))<x-active-filter-chip label="Registrasi" :value="request('registration_status') === 'registered' ? 'Sudah Registrasi' : 'Belum Registrasi'" :url="route('reports.employees', request()->except('registration_status', 'page'))" />@endif
            @if (request('employee_number_status'))<x-active-filter-chip label="NUP" :value="request('employee_number_status') === 'filled' ? 'Sudah Ada' : 'Belum Ada'" :url="route('reports.employees', request()->except('employee_number_status', 'page'))" />@endif
        </div>@endif
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        @if ($employees->count())
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 70px;">No</th>
                            <th>Pegawai</th>
                            <th>Unit & Jabatan</th>
                            <th>Kontak</th>
                            <th>Status</th>
                            <th>Registrasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($employees as $employee)
                            @php
                                $employment = $employmentBadges[$employee->employment_status] ?? ['label' => $employee->employment_status ?: '-', 'class' => 'bg-light-secondary text-secondary'];
                                $verification = $verificationBadges[$employee->verification_status] ?? ['label' => $employee->verification_status ?: '-', 'class' => 'bg-light-secondary text-secondary'];
                            @endphp
                            <tr>
                                <td>{{ $employees->firstItem() + $loop->index }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $employee->full_name }}</div>
                                    <div class="data-meta">NUP / Nomor Pegawai: {{ $employee->formatted_employee_number }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $employee->institution?->name ?: '-' }}</div>
                                    <div class="data-meta">{{ $employee->position?->name ?: '-' }}</div>
                                    <div class="data-meta">Jenis: {{ $employeeTypes[$employee->employee_type] ?? ($employee->employee_type ?: '-') }}</div>
                                </td>
                                <td>
                                    <div>{{ $employee->email ?: '-' }}</div>
                                    <div class="data-meta">{{ $employee->phone ?: '-' }}</div>
                                </td>
                                <td>
                                    <div class="status-stack">
                                        <span class="badge {{ $employment['class'] }}">{{ $employment['label'] }}</span>
                                        <span class="badge {{ $verification['class'] }}">{{ $verification['label'] }}</span>
                                    </div>
                                </td>
                                <td>
                                    @if ($employee->user_id)
                                        <span class="badge bg-light-success text-success">Sudah Registrasi</span>
                                    @else
                                        <span class="badge bg-light-warning text-warning">Belum Registrasi</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">
                <div class="avtar bg-light-secondary text-secondary"><i class="ti ti-database-off"></i></div>
                <h6 class="mb-1">{{ $hasActiveFilters ? 'Tidak ada pegawai yang sesuai dengan filter.' : 'Belum ada data pegawai' }}</h6>
                <p class="text-muted {{ $hasActiveFilters ? 'mb-3' : 'mb-0' }}">{{ $hasActiveFilters ? 'Ubah atau reset filter untuk melihat data lainnya.' : 'Data pegawai akan muncul setelah tersedia.' }}</p>
                @if ($hasActiveFilters)<a href="{{ route('reports.employees') }}" class="btn btn-light-primary"><i class="ti ti-filter-off"></i> Reset Filter</a>@endif
            </div>
        @endif
    </div>
    @if ($employees->hasPages())
        <div class="card-footer">
            {{ $employees->links() }}
        </div>
    @endif
</div>
@endsection
