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
            'aktif' => 'Aktif',
            'kontrak' => 'Kontrak',
            'honorer' => 'Honorer',
            'part_time' => 'Part Time',
            'nonaktif' => 'Nonaktif',
            'resign' => 'Resign',
        ];
        $verificationStatuses = [
            'draft' => 'Draft',
            'submitted' => 'Menunggu Verifikasi',
            'verified' => 'Terverifikasi',
            'rejected' => 'Ditolak',
        ];
        $verificationClasses = [
            'draft' => 'bg-light-secondary text-secondary',
            'submitted' => 'bg-light-warning text-warning',
            'verified' => 'bg-light-success text-success',
            'rejected' => 'bg-light-danger text-danger',
        ];
    @endphp

    <div class="page-header">
        <div class="page-block">
            <div class="row align-items-center">
                <div class="col-md-12">
                    <div class="page-header-title">
                        <h5 class="m-b-10">Data Pegawai</h5>
                    </div>

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
        <div class="card-header">
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <h5 class="mb-0">Daftar Pegawai</h5>

                <a href="{{ route('employees.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus"></i>
                    Tambah Pegawai
                </a>
            </div>
        </div>

        <div class="card-body">
            <form method="GET" action="{{ route('employees.index') }}" class="row g-2 mb-3">
                <div class="col-md-4">
                    <input
                        type="search"
                        name="search"
                        value="{{ $search }}"
                        class="form-control"
                        placeholder="Cari nama, email, HP, NIK, atau nomor pegawai"
                    >
                </div>

                <div class="col-md-2">
                    <select name="institution_id" class="form-select">
                        <option value="">Semua unit</option>
                        @foreach ($institutions as $institution)
                            <option value="{{ $institution->id }}" @selected((string) request('institution_id') === (string) $institution->id)>
                                {{ $institution->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <select name="position_id" class="form-select">
                        <option value="">Semua jabatan</option>
                        @foreach ($positions as $position)
                            <option value="{{ $position->id }}" @selected((string) request('position_id') === (string) $position->id)>
                                {{ $position->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <select name="verification_status" class="form-select">
                        <option value="">Semua verifikasi</option>
                        @foreach ($verificationStatuses as $value => $label)
                            <option value="{{ $value }}" @selected(request('verification_status') === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <select name="employment_status" class="form-select">
                        <option value="">Semua status</option>
                        @foreach ($employmentStatuses as $value => $label)
                            <option value="{{ $value }}" @selected(request('employment_status') === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="ti ti-filter"></i>
                        Filter
                    </button>

                    <a href="{{ route('employees.index') }}" class="btn btn-light-secondary">
                        Reset
                    </a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover table-borderless mb-0">
                    <thead>
                        <tr>
                            <th style="width: 70px;">No</th>
                            <th>Nama Pegawai</th>
                            <th>Nomor Pegawai</th>
                            <th>Unit Kerja</th>
                            <th>Jabatan</th>
                            <th>Email/HP</th>
                            <th>Jenis Pegawai</th>
                            <th>Status Kerja</th>
                            <th>Status Verifikasi</th>
                            <th class="text-end" style="width: 360px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($employees as $employee)
                            <tr>
                                <td>{{ $employees->firstItem() + $loop->index }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $employee->full_name }}</div>
                                </td>
                                <td>
                                    @if ($employee->employee_number)
                                        <div>{{ $employee->employee_number }}</div>
                                    @else
                                        <span class="badge bg-light-secondary text-secondary">Belum dibuat</span>
                                    @endif

                                    @if ($employee->foundation_registry_number)
                                        <div class="small text-muted">
                                            No. Buku: {{ $employee->foundation_registry_number }}
                                        </div>
                                    @endif
                                </td>
                                <td>{{ $employee->institution?->name ?? '-' }}</td>
                                <td>{{ $employee->position?->name ?? '-' }}</td>
                                <td>
                                    <div>{{ $employee->email ?? '-' }}</div>
                                    <small class="text-muted">{{ $employee->phone ?? '-' }}</small>
                                </td>
                                <td>{{ $employeeTypes[$employee->employee_type] ?? $employee->employee_type }}</td>
                                <td>
                                    <span class="badge {{ $employee->employment_status === 'aktif' ? 'bg-light-success text-success' : 'bg-light-secondary text-secondary' }}">
                                        {{ $employmentStatuses[$employee->employment_status] ?? $employee->employment_status }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ $verificationClasses[$employee->verification_status] ?? 'bg-light-secondary text-secondary' }}">
                                        {{ $verificationStatuses[$employee->verification_status] ?? $employee->verification_status }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('employees.show', $employee) }}" class="btn btn-sm btn-light-secondary">
                                        <i class="ti ti-eye"></i>
                                        Detail
                                    </a>

                                    <a href="{{ route('employees.edit', $employee) }}" class="btn btn-sm btn-light-primary">
                                        <i class="ti ti-edit"></i>
                                        Edit
                                    </a>

                                    @if ($employee->isSubmitted())
                                        <a href="{{ route('verifications.show', $employee) }}" class="btn btn-sm btn-light-success">
                                            <i class="ti ti-user-check"></i>
                                            Verifikasi
                                        </a>
                                    @endif

                                    @if ($employee->user_id === null)
                                        <form action="{{ route('employees.invitations.generate', $employee) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-primary">
                                                <i class="ti ti-mail-plus"></i>
                                                Buat Undangan
                                            </button>
                                        </form>
                                    @else
                                        <span class="badge bg-light-success text-success">Sudah Registrasi</span>
                                    @endif

                                    <form action="{{ route('employees.destroy', $employee) }}" method="POST" class="d-inline" onsubmit="return confirm('Nonaktifkan pegawai ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light-danger" @disabled($employee->employment_status === 'nonaktif')>
                                            <i class="ti ti-user-off"></i>
                                            Nonaktifkan
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted">Belum ada data pegawai.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $employees->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
@endsection
