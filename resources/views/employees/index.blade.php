@extends('layouts.admin')

@section('title', 'Data Pegawai | YAPISTA HRIS')

@section('content')
    @php
        $employeeTypes = [
            'guru' => 'Guru',
            'dosen' => 'Dosen',
            'tenaga_kependidikan' => 'Tenaga Kependidikan',
            'staff_yayasan' => 'Staff Yayasan',
            'security' => 'Security',
            'cleaning_service' => 'Cleaning Service',
            'driver' => 'Driver',
            'teknisi' => 'Teknisi',
        ];
        $employmentStatuses = [
            'aktif' => ['label' => 'Aktif', 'class' => 'bg-light-success text-success'],
            'kontrak' => ['label' => 'Kontrak', 'class' => 'bg-light-primary text-primary'],
            'honorer' => ['label' => 'Honorer', 'class' => 'bg-light-warning text-warning'],
            'part_time' => ['label' => 'Part Time', 'class' => 'bg-light-info text-info'],
            'nonaktif' => ['label' => 'Nonaktif', 'class' => 'bg-light-danger text-danger'],
            'resign' => ['label' => 'Resign', 'class' => 'bg-light-secondary text-secondary'],
        ];
        $verificationStatuses = [
            'draft' => ['label' => 'Draft', 'class' => 'bg-light-secondary text-secondary'],
            'submitted' => ['label' => 'Menunggu Verifikasi', 'class' => 'bg-light-primary text-primary'],
            'verified' => ['label' => 'Terverifikasi', 'class' => 'bg-light-success text-success'],
            'rejected' => ['label' => 'Ditolak', 'class' => 'bg-light-danger text-danger'],
        ];
        $summaryCards = [
            ['label' => 'Total Pegawai', 'value' => $totalEmployees, 'icon' => 'ti-users', 'class' => 'bg-light-primary text-primary'],
            ['label' => 'Pegawai Aktif', 'value' => $activeEmployees, 'icon' => 'ti-user-check', 'class' => 'bg-light-success text-success'],
            ['label' => 'Menunggu Verifikasi', 'value' => $submittedEmployees, 'icon' => 'ti-clock-check', 'class' => 'bg-light-warning text-warning'],
            ['label' => 'Sudah Registrasi', 'value' => $registeredEmployees, 'icon' => 'ti-user-shield', 'class' => 'bg-light-info text-info'],
        ];
    @endphp

    <div class="page-header">
        <div class="page-block">
            <div class="row align-items-center">
                <div class="col-md-12">
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item" aria-current="page">Pegawai</li>
                        <li class="breadcrumb-item" aria-current="page">Data Pegawai</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1">Data Pegawai</h4>
                    <p class="text-muted mb-0">Kelola data pegawai, registrasi akun, verifikasi, dan ID Card pegawai.</p>
                </div>

                <a href="{{ route('employees.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus"></i>
                    Tambah Pegawai
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        @foreach ($summaryCards as $card)
            <div class="col-md-6 col-xl-3">
                <div class="card">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="avtar avtar-s {{ $card['class'] }}">
                                <i class="ti {{ $card['icon'] }} f-20"></i>
                            </div>
                            <div>
                                <div class="text-muted small">{{ $card['label'] }}</div>
                                <h4 class="mb-0">{{ number_format($card['value']) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Filter Pegawai</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('employees.index') }}" class="row g-3">
                <div class="col-lg-6">
                    <label for="search" class="form-label">Pencarian</label>
                    <input
                        id="search"
                        type="search"
                        name="search"
                        value="{{ $search }}"
                        class="form-control"
                        placeholder="Cari nama, email, HP, NIK, atau nomor pegawai"
                    >
                </div>

                <div class="col-md-6 col-lg-3">
                    <label for="institution_id" class="form-label">Unit Kerja</label>
                    <select id="institution_id" name="institution_id" class="form-select">
                        <option value="">Semua unit</option>
                        @foreach ($institutions as $institution)
                            <option value="{{ $institution->id }}" @selected((string) request('institution_id') === (string) $institution->id)>
                                {{ $institution->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 col-lg-3">
                    <label for="position_id" class="form-label">Jabatan</label>
                    <select id="position_id" name="position_id" class="form-select">
                        <option value="">Semua jabatan</option>
                        @foreach ($positions as $position)
                            <option value="{{ $position->id }}" @selected((string) request('position_id') === (string) $position->id)>
                                {{ $position->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 col-lg-3">
                    <label for="verification_status" class="form-label">Status Verifikasi</label>
                    <select id="verification_status" name="verification_status" class="form-select">
                        <option value="">Semua verifikasi</option>
                        @foreach ($verificationStatuses as $value => $status)
                            <option value="{{ $value }}" @selected(request('verification_status') === $value)>
                                {{ $status['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 col-lg-3">
                    <label for="employment_status" class="form-label">Status Kerja</label>
                    <select id="employment_status" name="employment_status" class="form-select">
                        <option value="">Semua status</option>
                        @foreach ($employmentStatuses as $value => $status)
                            <option value="{{ $value }}" @selected(request('employment_status') === $value)>
                                {{ $status['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-12 col-lg-6 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-filter"></i>
                        Filter
                    </button>

                    <a href="{{ route('employees.index') }}" class="btn btn-light-secondary">
                        Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <h5 class="mb-0">Daftar Pegawai</h5>
                <span class="text-muted small">{{ $employees->total() }} data ditampilkan berdasarkan filter saat ini</span>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4" style="width: 70px;">No</th>
                            <th>Pegawai</th>
                            <th>Unit & Jabatan</th>
                            <th>Kontak</th>
                            <th>Status</th>
                            <th>Registrasi</th>
                            <th class="text-end pe-4" style="width: 170px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($employees as $employee)
                            @php
                                $employmentStatus = $employmentStatuses[$employee->employment_status] ?? [
                                    'label' => $employee->employment_status ?? '-',
                                    'class' => 'bg-light-secondary text-secondary',
                                ];
                                $verificationStatus = $verificationStatuses[$employee->verification_status] ?? [
                                    'label' => $employee->verification_status ?? '-',
                                    'class' => 'bg-light-secondary text-secondary',
                                ];
                                $idCardRoute = Route::has('employees.id-card.show')
                                    ? route('employees.id-card.show', $employee)
                                    : null;
                            @endphp

                            <tr>
                                <td class="ps-4">{{ $employees->firstItem() + $loop->index }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $employee->full_name }}</div>
                                    <div class="small mt-1">
                                        @if ($employee->employee_number)
                                            <span class="text-muted">NUP / Nomor Pegawai: {{ $employee->employee_number }}</span>
                                        @else
                                            <span class="badge bg-light-secondary text-secondary">Belum diisi</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-medium">{{ $employee->institution?->name ?? '-' }}</div>
                                    <div class="text-muted small">{{ $employee->position?->name ?? '-' }}</div>
                                    <div class="text-muted small">Jenis: {{ $employeeTypes[$employee->employee_type] ?? $employee->employee_type ?? '-' }}</div>
                                </td>
                                <td>
                                    <div>{{ $employee->email ?? '-' }}</div>
                                    <div class="text-muted small">{{ $employee->phone ?? '-' }}</div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column align-items-start gap-1">
                                        <span class="badge {{ $employmentStatus['class'] }}">{{ $employmentStatus['label'] }}</span>
                                        <span class="badge {{ $verificationStatus['class'] }}">{{ $verificationStatus['label'] }}</span>
                                    </div>
                                </td>
                                <td>
                                    @if ($employee->user_id)
                                        <span class="badge bg-light-success text-success">Sudah Registrasi</span>
                                    @else
                                        <span class="badge bg-light-warning text-warning">Belum Registrasi</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <div class="table-actions">
                                        <a href="{{ route('employees.show', $employee) }}" class="btn btn-sm btn-light-secondary">
                                            <i class="ti ti-eye"></i>
                                            Detail
                                        </a>

                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-light-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                Aksi
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <a class="dropdown-item" href="{{ route('employees.edit', $employee) }}">
                                                    <i class="ti ti-edit me-2"></i>
                                                    Edit
                                                </a>

                                                @if ($employee->user_id === null)
                                                    <form action="{{ route('employees.invitations.generate', $employee) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item">
                                                            <i class="ti ti-mail-plus me-2"></i>
                                                            Buat Undangan
                                                        </button>
                                                    </form>
                                                @endif

                                                @if ($idCardRoute)
                                                    <a class="dropdown-item" href="{{ $idCardRoute }}">
                                                        <i class="ti ti-id me-2"></i>
                                                        Lihat ID Card
                                                    </a>
                                                @endif

                                                @if ($employee->isSubmitted())
                                                    <a class="dropdown-item" href="{{ route('verifications.show', $employee) }}">
                                                        <i class="ti ti-user-check me-2"></i>
                                                        Detail Verifikasi
                                                    </a>
                                                @endif

                                                <div class="dropdown-divider"></div>

                                                <form action="{{ route('employees.destroy', $employee) }}" method="POST" onsubmit="return confirm('Nonaktifkan pegawai ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger" @disabled($employee->employment_status === 'nonaktif')>
                                                        <i class="ti ti-user-off me-2"></i>
                                                        Nonaktifkan
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="text-center py-5">
                                        <div class="avtar avtar-l bg-light-secondary text-secondary mx-auto mb-3">
                                            <i class="ti ti-users f-28"></i>
                                        </div>
                                        <h5 class="mb-1">Belum ada data pegawai.</h5>
                                        <p class="text-muted mb-3">Silakan tambahkan pegawai baru terlebih dahulu.</p>
                                        <a href="{{ route('employees.create') }}" class="btn btn-primary">
                                            <i class="ti ti-plus"></i>
                                            Tambah Pegawai
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($employees->hasPages())
            <div class="card-footer">
                {{ $employees->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
@endsection
