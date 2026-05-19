@extends('layouts.admin')

@section('title', 'Detail Pegawai | YAPISTA HRIS')

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
        $employmentClasses = [
            'aktif' => 'bg-light-success text-success',
            'kontrak' => 'bg-light-primary text-primary',
            'honorer' => 'bg-light-warning text-warning',
            'part_time' => 'bg-light-info text-info',
            'nonaktif' => 'bg-light-danger text-danger',
            'resign' => 'bg-light-secondary text-secondary',
        ];
        $verificationClasses = [
            'draft' => 'bg-light-secondary text-secondary',
            'submitted' => 'bg-light-primary text-primary',
            'verified' => 'bg-light-success text-success',
            'rejected' => 'bg-light-danger text-danger',
        ];
        $documentStatusClasses = [
            'pending' => 'bg-light-warning text-warning',
            'valid' => 'bg-light-success text-success',
            'rejected' => 'bg-light-danger text-danger',
        ];
        $photoUrl = $employee->photo
            ? asset('storage/'.$employee->photo)
            : asset('assets/images/user/avatar-2.jpg');
    @endphp

    <div class="page-header">
        <div class="page-block">
            <div class="row align-items-center">
                <div class="col-md-12">
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('employees.index') }}">Data Pegawai</a></li>
                        <li class="breadcrumb-item" aria-current="page">Detail</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="card page-intro-card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <h4 class="mb-1">Detail Pegawai</h4>
                    <p class="mb-0 text-muted">Ringkasan biodata, unit kerja, status verifikasi, dan dokumen pegawai.</p>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('employees.index') }}" class="btn btn-light-secondary">Kembali</a>
                    <a href="{{ route('employees.edit', $employee) }}" class="btn btn-primary">
                        <i class="ti ti-edit"></i>
                        Edit
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center">
                    <img src="{{ $photoUrl }}" alt="{{ $employee->full_name }}" class="rounded-circle wid-100 hei-100 mb-3" style="object-fit: cover;">
                    <h4 class="mb-1">{{ $employee->full_name }}</h4>
                    <p class="text-muted mb-2">{{ $employee->employee_number ?? 'Belum dibuat' }}</p>
                    <span class="badge {{ $employmentClasses[$employee->employment_status] ?? 'bg-light-secondary text-secondary' }}">
                        {{ $employmentStatuses[$employee->employment_status] ?? $employee->employment_status }}
                    </span>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Informasi Dasar</h5>
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
                            <small class="text-muted d-block">Jenis Pegawai</small>
                            {{ $employeeTypes[$employee->employee_type] ?? $employee->employee_type }}
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block">Tanggal Masuk</small>
                            {{ $employee->join_date?->format('d M Y') ?? '-' }}
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block">Nomor Urut Buku Yayasan</small>
                            {{ $employee->foundation_registry_number ?? '-' }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Unit dan Jabatan</h5>
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
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Kontak dan Verifikasi</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block">Email</small>
                            {{ $employee->email ?? '-' }}
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block">Nomor HP</small>
                            {{ $employee->phone ?? '-' }}
                        </div>
                        <div class="col-12 mb-3">
                            <small class="text-muted d-block">Alamat</small>
                            {{ $employee->address ?? '-' }}
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block">Status Verifikasi</small>
                            <span class="badge {{ $verificationClasses[$employee->verification_status] ?? 'bg-light-secondary text-secondary' }}">
                                {{ $verificationStatuses[$employee->verification_status] ?? $employee->verification_status }}
                            </span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block">Diverifikasi Oleh</small>
                            {{ $employee->verifier?->name ?? '-' }}
                        </div>
                        <div class="col-12">
                            <small class="text-muted d-block">Catatan Verifikasi</small>
                            {{ $employee->verification_note ?? '-' }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Dokumen Pegawai</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Jenis Dokumen</th>
                                    <th>Nama File</th>
                                    <th>Status</th>
                                    <th>Tanggal Upload</th>
                                    <th>Catatan</th>
                                    <th class="text-end">File</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($employee->documents as $document)
                                    <tr>
                                        <td>{{ $document->document_type_label }}</td>
                                        <td>{{ $document->original_name ?? '-' }}</td>
                                        <td>
                                            <span class="badge {{ $documentStatusClasses[$document->status] ?? 'bg-light-secondary text-secondary' }}">
                                                {{ ['pending' => 'Menunggu', 'valid' => 'Valid', 'rejected' => 'Ditolak'][$document->status] ?? $document->status }}
                                            </span>
                                        </td>
                                        <td>{{ $document->uploaded_at?->format('d M Y H:i') ?? '-' }}</td>
                                        <td>{{ $document->note ?? '-' }}</td>
                                        <td class="text-end">
                                            <div class="table-actions">
                                                <a href="{{ asset('storage/'.$document->file_path) }}" target="_blank" class="btn btn-sm btn-light-primary">
                                                    <i class="ti ti-download"></i>
                                                    Lihat
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6">
                                            <div class="empty-state">
                                                <div class="avtar avtar-l bg-light-secondary text-secondary">
                                                    <i class="ti ti-files-off f-28"></i>
                                                </div>
                                                <h5 class="mb-1">Belum ada dokumen.</h5>
                                                <p class="text-muted mb-0">Dokumen akan tampil setelah pegawai menguploadnya.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
