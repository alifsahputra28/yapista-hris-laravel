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
@endphp

<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <h4 class="mb-1">Laporan Pegawai</h4>
        <p class="text-muted mb-0">Pantau data pegawai, registrasi akun, NUP / Nomor Pegawai, dan status verifikasi.</p>
    </div>
    <a href="{{ route('reports.employees.export', request()->query()) }}" class="btn btn-primary">
        <i class="ti ti-file-spreadsheet me-1"></i> Export Excel
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="card summary-card mb-0">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="avtar bg-light-primary text-primary"><i class="ti ti-users"></i></div>
                <div>
                    <div class="text-muted small">Total Pegawai</div>
                    <h5 class="mb-0">{{ number_format($totalEmployees) }}</h5>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card summary-card mb-0">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="avtar bg-light-success text-success"><i class="ti ti-user-check"></i></div>
                <div>
                    <div class="text-muted small">Pegawai Aktif</div>
                    <h5 class="mb-0">{{ number_format($activeEmployees) }}</h5>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card summary-card mb-0">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="avtar bg-light-info text-info"><i class="ti ti-login"></i></div>
                <div>
                    <div class="text-muted small">Sudah Registrasi</div>
                    <h5 class="mb-0">{{ number_format($registeredEmployees) }}</h5>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card summary-card mb-0">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="avtar bg-light-warning text-warning"><i class="ti ti-shield-check"></i></div>
                <div>
                    <div class="text-muted small">Terverifikasi</div>
                    <h5 class="mb-0">{{ number_format($verifiedEmployees) }}</h5>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card filter-card mb-4">
    <div class="card-body">
        <form action="{{ route('reports.employees') }}" method="GET">
            <div class="row g-3">
                <div class="col-lg-4">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" name="search" id="search" class="form-control" value="{{ request('search') }}" placeholder="Nama, email, HP, atau NUP / nomor pegawai">
                </div>
                <div class="col-md-6 col-lg-4">
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
                <div class="col-md-6 col-lg-4">
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
                <div class="col-md-6 col-xl-2">
                    <label for="employee_type" class="form-label">Jenis Pegawai</label>
                    <select name="employee_type" id="employee_type" class="form-select">
                        <option value="">Semua</option>
                        @foreach ($employeeTypes as $value => $label)
                            <option value="{{ $value }}" @selected(request('employee_type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-xl-2">
                    <label for="employment_status" class="form-label">Status Kerja</label>
                    <select name="employment_status" id="employment_status" class="form-select">
                        <option value="">Semua</option>
                        @foreach ($employmentStatuses as $value => $label)
                            <option value="{{ $value }}" @selected(request('employment_status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-xl-2">
                    <label for="verification_status" class="form-label">Verifikasi</label>
                    <select name="verification_status" id="verification_status" class="form-select">
                        <option value="">Semua</option>
                        @foreach ($verificationStatuses as $value => $label)
                            <option value="{{ $value }}" @selected(request('verification_status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-xl-2">
                    <label for="registration_status" class="form-label">Registrasi</label>
                    <select name="registration_status" id="registration_status" class="form-select">
                        <option value="">Semua</option>
                        <option value="registered" @selected(request('registration_status') === 'registered')>Sudah Registrasi</option>
                        <option value="unregistered" @selected(request('registration_status') === 'unregistered')>Belum Registrasi</option>
                    </select>
                </div>
                <div class="col-md-6 col-xl-2">
                    <label for="employee_number_status" class="form-label">Status NUP / Nomor Pegawai</label>
                    <select name="employee_number_status" id="employee_number_status" class="form-select">
                        <option value="">Semua</option>
                        <option value="filled" @selected(request('employee_number_status') === 'filled')>Sudah Ada</option>
                        <option value="empty" @selected(request('employee_number_status') === 'empty')>Belum Ada</option>
                    </select>
                </div>
                <div class="col-md-6 col-xl-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="ti ti-filter me-1"></i> Filter
                    </button>
                    <a href="{{ route('reports.employees') }}" class="btn btn-light">Reset</a>
                </div>
            </div>
        </form>
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
                <h6 class="mb-1">Belum ada data pegawai</h6>
                <p class="text-muted mb-0">Ubah filter atau tambahkan data pegawai terlebih dahulu.</p>
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
