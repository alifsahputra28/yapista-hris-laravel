@extends('layouts.admin')

@section('title', 'Verifikasi Pegawai | YAPISTA HRIS')

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
                        <h5 class="m-b-10">Verifikasi Pegawai</h5>
                    </div>

                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item" aria-current="page">Verifikasi Pegawai</li>
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
            <h5 class="mb-0">Filter Verifikasi</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('verifications.index') }}" class="row g-2">
                <div class="col-md-3">
                    <input type="search" name="search" value="{{ $search }}" class="form-control" placeholder="Cari nama, email, HP, NIK, nomor">
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
                    <select name="employee_type" class="form-select">
                        <option value="">Semua jenis</option>
                        @foreach ($employeeTypes as $value => $label)
                            <option value="{{ $value }}" @selected(request('employee_type') === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <select name="verification_status" class="form-select">
                        @foreach (['submitted', 'verified', 'rejected'] as $status)
                            <option value="{{ $status }}" @selected($verificationStatus === $status)>
                                {{ $verificationStatuses[$status] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-1">
                    <button type="submit" class="btn btn-outline-primary w-100">
                        <i class="ti ti-filter"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Daftar Pegawai</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-borderless mb-0">
                    <thead>
                        <tr>
                            <th style="width: 70px;">No</th>
                            <th>Nama Pegawai</th>
                            <th>Unit Kerja</th>
                            <th>Jabatan</th>
                            <th>Email/HP</th>
                            <th>Status Verifikasi</th>
                            <th>Jumlah Dokumen</th>
                            <th>Tanggal Diajukan / Updated At</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($employees as $employee)
                            <tr>
                                <td>{{ $employees->firstItem() + $loop->index }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $employee->full_name }}</div>
                                    <small class="text-muted">{{ $employee->employee_number ?? 'Belum dibuat' }}</small>
                                </td>
                                <td>{{ $employee->institution?->name ?? '-' }}</td>
                                <td>{{ $employee->position?->name ?? '-' }}</td>
                                <td>
                                    <div>{{ $employee->email ?? '-' }}</div>
                                    <small class="text-muted">{{ $employee->phone ?? '-' }}</small>
                                </td>
                                <td>
                                    <span class="badge {{ $verificationClasses[$employee->verification_status] ?? 'bg-light-secondary text-secondary' }}">
                                        {{ $verificationStatuses[$employee->verification_status] ?? $employee->verification_status }}
                                    </span>
                                </td>
                                <td>{{ $employee->documents->count() }}</td>
                                <td>{{ $employee->updated_at?->format('d M Y H:i') ?? '-' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('verifications.show', $employee) }}" class="btn btn-sm btn-primary">
                                        <i class="ti ti-user-check"></i>
                                        Detail Verifikasi
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted">Belum ada data pegawai yang menunggu verifikasi.</td>
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
