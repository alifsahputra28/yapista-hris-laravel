@extends('layouts.admin')

@section('title', 'Profil Saya | YAPISTA HRIS')

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
        $photoUrl = $employee->photo ? asset('storage/'.$employee->photo) : asset('assets/images/user/avatar-2.jpg');
        $ktpDocument = $employee->documents->firstWhere('document_type', 'ktp');
    @endphp

    <div class="page-header">
        <div class="page-block">
            <div class="row align-items-center">
                <div class="col-md-12">
                    <div class="page-header-title">
                        <h5 class="m-b-10">Profil Saya</h5>
                    </div>

                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('pegawai.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item" aria-current="page">Profil Saya</li>
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

    @if ($employee->isSubmitted())
        <div class="alert alert-warning">Data Anda sedang menunggu verifikasi HR.</div>
    @endif

    @if ($employee->isVerified())
        <div class="alert alert-success">Data Anda sudah diverifikasi.</div>
    @endif

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center">
                    <img src="{{ $photoUrl }}" alt="{{ $employee->full_name }}" class="rounded-circle wid-100 hei-100 mb-3" style="object-fit: cover;">
                    <h4 class="mb-1">{{ $employee->full_name }}</h4>
                    <p class="text-muted mb-2">{{ $employee->employee_number ?? 'Belum dibuat' }}</p>
                    <span class="badge {{ $verificationClasses[$employee->verification_status] ?? 'bg-light-secondary text-secondary' }}">
                        {{ $verificationStatuses[$employee->verification_status] ?? $employee->verification_status }}
                    </span>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Ringkasan Dokumen</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total dokumen</span>
                        <strong>{{ $employee->documents->count() }}</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Dokumen KTP</span>
                        <span class="badge {{ $ktpDocument ? 'bg-light-success text-success' : 'bg-light-danger text-danger' }}">
                            {{ $ktpDocument ? 'Ada' : 'Belum ada' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Data Pribadi</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block">Nama Lengkap</small>
                            {{ $employee->full_name }}
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block">NIK</small>
                            {{ $employee->nik ?? '-' }}
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block">Jenis Kelamin</small>
                            {{ $employee->gender === 'male' ? 'Laki-laki' : ($employee->gender === 'female' ? 'Perempuan' : '-') }}
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block">Tempat, Tanggal Lahir</small>
                            {{ $employee->birth_place ?? '-' }}{{ $employee->birth_date ? ', '.$employee->birth_date->format('d M Y') : '' }}
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block">Nomor HP</small>
                            {{ $employee->phone ?? '-' }}
                        </div>
                        <div class="col-12 mb-3">
                            <small class="text-muted d-block">Alamat</small>
                            {{ $employee->address ?? '-' }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Data Kepegawaian</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block">Unit Kerja</small>
                            {{ $employee->institution?->name ?? '-' }}
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block">Jabatan</small>
                            {{ $employee->position?->name ?? '-' }}
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block">Jenis Pegawai</small>
                            {{ $employeeTypes[$employee->employee_type] ?? $employee->employee_type }}
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block">Status Kepegawaian</small>
                            {{ $employmentStatuses[$employee->employment_status] ?? $employee->employment_status }}
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block">Tanggal Masuk</small>
                            {{ $employee->join_date?->format('d M Y') ?? '-' }}
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block">Nomor Urut Buku Yayasan</small>
                            {{ $employee->foundation_registry_number ?? '-' }}
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block">Nomor Pegawai</small>
                            {{ $employee->employee_number ?? 'Belum dibuat' }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
                @if ($employee->canEditProfile())
                    <a href="{{ route('pegawai.profile.edit') }}" class="btn btn-primary">
                        <i class="ti ti-edit"></i>
                        Edit Biodata
                    </a>
                @endif

                <a href="{{ route('pegawai.documents.index') }}" class="btn btn-light-primary">
                    <i class="ti ti-files"></i>
                    Dokumen Saya
                </a>

                @if ($employee->isDraft() || $employee->isRejected())
                    <form method="POST" action="{{ route('pegawai.profile.submit') }}">
                        @csrf
                        <button type="submit" class="btn btn-success">
                            <i class="ti ti-send"></i>
                            Ajukan Verifikasi
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endsection
