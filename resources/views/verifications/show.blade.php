@extends('layouts.admin')

@section('title', 'Detail Verifikasi Pegawai | YAPISTA HRIS')

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
        $documentStatusClasses = [
            'pending' => 'bg-light-warning text-warning',
            'valid' => 'bg-light-success text-success',
            'rejected' => 'bg-light-danger text-danger',
        ];
        $photoUrl = $employee->photo ? asset('storage/'.$employee->photo) : asset('assets/images/user/avatar-2.jpg');
    @endphp

    <div class="page-header">
        <div class="page-block">
            <div class="row align-items-center">
                <div class="col-md-12">
                    <div class="page-header-title">
                        <h5 class="m-b-10">Detail Verifikasi Pegawai</h5>
                    </div>

                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('verifications.index') }}">Verifikasi Pegawai</a></li>
                        <li class="breadcrumb-item" aria-current="page">Detail</li>
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

    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    @if ($employee->isVerified())
        <div class="alert alert-success">Pegawai sudah diverifikasi.</div>
    @elseif ($employee->isRejected())
        <div class="alert alert-danger">Data pegawai ditolak dan menunggu perbaikan.</div>
    @elseif ($employee->isDraft())
        <div class="alert alert-secondary">Pegawai belum mengajukan verifikasi.</div>
    @endif

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Foto Pegawai</h5>
                </div>
                <div class="card-body text-center">
                    <img src="{{ $photoUrl }}" alt="{{ $employee->full_name }}" class="rounded-circle wid-100 hei-100 mb-3" style="object-fit: cover;">
                    <h4 class="mb-1">{{ $employee->full_name }}</h4>
                    <p class="text-muted mb-2">{{ $employee->employee_number ?? 'Belum dibuat' }}</p>
                    <span class="badge {{ $verificationClasses[$employee->verification_status] ?? 'bg-light-secondary text-secondary' }}">
                        {{ $verificationStatuses[$employee->verification_status] ?? $employee->verification_status }}
                    </span>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Biodata Pegawai</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3"><small class="text-muted d-block">Nama Lengkap</small>{{ $employee->full_name }}</div>
                        <div class="col-md-6 mb-3"><small class="text-muted d-block">NIK</small>{{ $employee->nik ?? '-' }}</div>
                        <div class="col-md-6 mb-3"><small class="text-muted d-block">Jenis Kelamin</small>{{ $employee->gender === 'male' ? 'Laki-laki' : ($employee->gender === 'female' ? 'Perempuan' : '-') }}</div>
                        <div class="col-md-6 mb-3"><small class="text-muted d-block">Tempat, Tanggal Lahir</small>{{ $employee->birth_place ?? '-' }}{{ $employee->birth_date ? ', '.$employee->birth_date->format('d M Y') : '' }}</div>
                        <div class="col-md-6 mb-3"><small class="text-muted d-block">Nomor HP</small>{{ $employee->phone ?? '-' }}</div>
                        <div class="col-md-6 mb-3"><small class="text-muted d-block">Email</small>{{ $employee->email ?? '-' }}</div>
                        <div class="col-12"><small class="text-muted d-block">Alamat</small>{{ $employee->address ?? '-' }}</div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Data Kepegawaian</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3"><small class="text-muted d-block">Unit Kerja</small>{{ $employee->institution?->name ?? '-' }}</div>
                        <div class="col-md-6 mb-3"><small class="text-muted d-block">Jabatan</small>{{ $employee->position?->name ?? '-' }}</div>
                        <div class="col-md-6 mb-3"><small class="text-muted d-block">Jenis Pegawai</small>{{ $employeeTypes[$employee->employee_type] ?? $employee->employee_type }}</div>
                        <div class="col-md-6 mb-3"><small class="text-muted d-block">Status Kepegawaian</small>{{ $employmentStatuses[$employee->employment_status] ?? $employee->employment_status }}</div>
                        <div class="col-md-6 mb-3"><small class="text-muted d-block">Tanggal Masuk</small>{{ $employee->join_date?->format('d M Y') ?? '-' }}</div>
                        <div class="col-md-6 mb-3"><small class="text-muted d-block">Nomor Urut Buku Yayasan</small>{{ $employee->foundation_registry_number ?? '-' }}</div>
                        <div class="col-md-6 mb-3"><small class="text-muted d-block">Nomor Pegawai</small>{{ $employee->employee_number ?? 'Belum dibuat' }}</div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block">Status Verifikasi</small>
                            <span class="badge {{ $verificationClasses[$employee->verification_status] ?? 'bg-light-secondary text-secondary' }}">
                                {{ $verificationStatuses[$employee->verification_status] ?? $employee->verification_status }}
                            </span>
                        </div>
                        <div class="col-md-6 mb-3"><small class="text-muted d-block">Diverifikasi Oleh</small>{{ $employee->verifier?->name ?? '-' }}</div>
                        <div class="col-12"><small class="text-muted d-block">Catatan Verifikasi</small>{{ $employee->verification_note ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Dokumen Pegawai</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-borderless mb-0">
                    <thead>
                        <tr>
                            <th>Jenis Dokumen</th>
                            <th>Nama File</th>
                            <th>Ukuran</th>
                            <th>Status</th>
                            <th>Catatan</th>
                            <th>Tanggal Upload</th>
                            <th style="min-width: 280px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($employee->documents as $document)
                            <tr>
                                <td>{{ $document->document_type_label }}</td>
                                <td>{{ $document->original_name ?? '-' }}</td>
                                <td>{{ $document->file_size ? number_format($document->file_size / 1024, 1).' KB' : '-' }}</td>
                                <td>
                                    <span class="badge {{ $documentStatusClasses[$document->status] ?? 'bg-light-secondary text-secondary' }}">
                                        {{ $document->status }}
                                    </span>
                                </td>
                                <td>{{ $document->note ?? '-' }}</td>
                                <td>{{ $document->uploaded_at?->format('d M Y H:i') ?? '-' }}</td>
                                <td>
                                    <a href="{{ asset('storage/'.$document->file_path) }}" target="_blank" class="btn btn-sm btn-light-primary mb-2">
                                        <i class="ti ti-download"></i>
                                        Lihat
                                    </a>

                                    <form method="POST" action="{{ route('employee-documents.update-status', $document) }}" class="row g-2">
                                        @csrf
                                        @method('PATCH')
                                        <div class="col-md-4">
                                            <select name="status" class="form-select form-select-sm" required>
                                                <option value="valid" @selected($document->status === 'valid')>Valid</option>
                                                <option value="rejected" @selected($document->status === 'rejected')>Rejected</option>
                                            </select>
                                        </div>
                                        <div class="col-md-5">
                                            <textarea name="note" rows="1" class="form-control form-control-sm" placeholder="Catatan">{{ old('note', $document->note) }}</textarea>
                                        </div>
                                        <div class="col-md-3">
                                            <button type="submit" class="btn btn-sm btn-primary w-100">Simpan</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">Belum ada dokumen.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Aksi Verifikasi</h5>
        </div>
        <div class="card-body">
            @if ($employee->isSubmitted())
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <form method="POST" action="{{ route('verifications.approve', $employee) }}" onsubmit="return confirm('Approve data pegawai ini?')">
                        @csrf
                        <button type="submit" class="btn btn-success">
                            <i class="ti ti-check"></i>
                            Approve
                        </button>
                    </form>
                </div>

                <form method="POST" action="{{ route('verifications.reject', $employee) }}" onsubmit="return confirm('Reject data pegawai ini?')">
                    @csrf
                    <div class="form-group mb-3">
                        <label for="verification_note" class="form-label">Catatan Reject</label>
                        <textarea id="verification_note" name="verification_note" rows="4" class="form-control @error('verification_note') is-invalid @enderror" required>{{ old('verification_note') }}</textarea>
                        @error('verification_note')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-danger">
                        <i class="ti ti-x"></i>
                        Reject
                    </button>
                </form>
            @else
                <p class="mb-0 text-muted">Aksi approve/reject hanya tersedia untuk data dengan status Menunggu Verifikasi.</p>
            @endif
        </div>
    </div>
@endsection
