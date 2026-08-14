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
            'submitted' => 'bg-light-primary text-primary',
            'verified' => 'bg-light-success text-success',
            'rejected' => 'bg-light-danger text-danger',
        ];
        $documentStatusClasses = [
            'pending' => 'bg-light-warning text-warning',
            'valid' => 'bg-light-success text-success',
            'rejected' => 'bg-light-danger text-danger',
        ];
        $photoUrl = $employee->photo ? route('employees.photo', $employee) : asset('assets/images/user/avatar-2.jpg');
    @endphp

    <x-page-header
        title="Review {{ $employee->full_name }}"
        subtitle="Periksa data utama dan dokumen sebelum mengambil keputusan verifikasi."
        :breadcrumbs="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Verifikasi Pegawai', 'url' => route('verifications.index')],
            ['label' => 'Review'],
        ]"
        :badge-label="$verificationStatuses[$employee->verification_status] ?? $employee->verification_status"
        :badge-class="$verificationClasses[$employee->verification_status] ?? 'bg-light-secondary text-secondary'"
    >
        <x-slot:meta>
            <div class="d-flex align-items-center gap-3">
                <img src="{{ $photoUrl }}" alt="Foto {{ $employee->full_name }}" class="rounded-circle object-fit-cover" width="48" height="48">
                <x-employee-context :employee="$employee" />
            </div>
        </x-slot:meta>
        <x-slot:actions>
            <a href="{{ route('verifications.index') }}" class="btn btn-light-secondary">Kembali</a>
        </x-slot:actions>
    </x-page-header>

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

    <div class="row g-4">
        <div class="col-xl-6">
            <section class="content-section h-100 mb-0">
                <div class="content-section-header">
                    <h2>Biodata Pegawai</h2>
                </div>
                <div class="content-section-body">
                    <div class="row g-4">
                        <div class="col-md-6 mb-3"><small class="text-muted d-block">Nama Lengkap</small>{{ $employee->full_name }}</div>
                        <div class="col-md-6 mb-3"><small class="text-muted d-block">NIK</small>{{ $employee->masked_nik ?? '-' }}</div>
                        <div class="col-md-6 mb-3"><small class="text-muted d-block">Jenis Kelamin</small>{{ $employee->gender === 'male' ? 'Laki-laki' : ($employee->gender === 'female' ? 'Perempuan' : '-') }}</div>
                        <div class="col-md-6 mb-3"><small class="text-muted d-block">Tempat, Tanggal Lahir</small>{{ $employee->birth_place ?? '-' }}{{ $employee->birth_date ? ', '.$employee->birth_date->locale('id')->translatedFormat('d M Y') : '' }}</div>
                        <div class="col-md-6 mb-3"><small class="text-muted d-block">Nomor HP</small>{{ $employee->phone ?? '-' }}</div>
                        <div class="col-md-6 mb-3"><small class="text-muted d-block">Email</small>{{ $employee->email ?? '-' }}</div>
                        <div class="col-12"><small class="text-muted d-block">Alamat</small>{{ $employee->address ?? '-' }}</div>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-xl-6">
            <section class="content-section h-100 mb-0">
                <div class="content-section-header">
                    <h2>Data Kepegawaian</h2>
                </div>
                <div class="content-section-body">
                    <div class="row g-4">
                        <div class="col-md-6 mb-3"><small class="text-muted d-block">Unit Kerja</small>{{ $employee->institution?->name ?? '-' }}</div>
                        <div class="col-md-6 mb-3"><small class="text-muted d-block">Jabatan</small>{{ $employee->position?->name ?? '-' }}</div>
                        <div class="col-md-6 mb-3"><small class="text-muted d-block">Jenis Pegawai</small>{{ $employeeTypes[$employee->employee_type] ?? $employee->employee_type }}</div>
                        <div class="col-md-6 mb-3"><small class="text-muted d-block">Status Kepegawaian</small>{{ $employmentStatuses[$employee->employment_status] ?? $employee->employment_status }}</div>
                        <div class="col-md-6 mb-3"><small class="text-muted d-block">Tanggal Masuk</small>{{ $employee->join_date?->locale('id')->translatedFormat('d M Y') ?? '-' }}</div>
                        <div class="col-md-6 mb-3"><small class="text-muted d-block">NUP / Nomor Pegawai</small>{{ $employee->formatted_employee_number }}</div>
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
            </section>
        </div>
    </div>

    <section class="content-section">
        <div class="content-section-header">
            <h2>Dokumen Pegawai</h2>
        </div>
        <div class="content-section-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
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
                                        {{ ['pending' => 'Menunggu', 'valid' => 'Valid', 'rejected' => 'Ditolak'][$document->status] ?? $document->status }}
                                    </span>
                                </td>
                                <td>{{ $document->note ?? '-' }}</td>
                                <td>{{ $document->uploaded_at?->locale('id')->translatedFormat('d M Y H:i') ?? '-' }}</td>
                                <td>
                                    <div class="table-actions mb-2">
                                        <a href="{{ route('employee-documents.view', $document) }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-light-primary">
                                            <i class="ti ti-eye"></i>
                                            Lihat
                                        </a>
                                        <a href="{{ route('employee-documents.download', $document) }}" class="btn btn-sm btn-light-secondary" title="Download dokumen">
                                            <i class="ti ti-download"></i>
                                        </a>
                                    </div>

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
                                <td colspan="7">
                                    <div class="empty-state">
                                        <div class="avtar avtar-l bg-light-secondary text-secondary">
                                            <i class="ti ti-files-off f-28"></i>
                                        </div>
                                        <h5 class="mb-1">Belum ada dokumen.</h5>
                                        <p class="text-muted mb-0">Pegawai belum mengupload dokumen pendukung.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="content-section">
        <div class="content-section-header">
            <h2>Aksi Verifikasi</h2>
        </div>
        <div class="content-section-body">
            @if ($employee->isSubmitted())
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <form method="POST" action="{{ route('verifications.approve', $employee) }}" data-confirm-title="Setujui Data Pegawai?" data-confirm-message="Data pegawai akan ditandai terverifikasi. Lanjutkan?">
                        @csrf
                        <button type="submit" class="btn btn-success">
                            <i class="ti ti-check"></i>
                            Setujui
                        </button>
                    </form>
                </div>

                <form method="POST" action="{{ route('verifications.reject', $employee) }}" data-confirm-title="Tolak Data Pegawai?" data-confirm-message="Data akan dikembalikan kepada pegawai untuk diperbaiki. Lanjutkan?">
                    @csrf
                    <div class="form-group mb-3">
                        <label for="verification_note" class="form-label">Catatan Penolakan</label>
                        <textarea id="verification_note" name="verification_note" rows="4" class="form-control @error('verification_note') is-invalid @enderror" required>{{ old('verification_note') }}</textarea>
                        @error('verification_note')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-danger">
                        <i class="ti ti-x"></i>
                        Tolak
                    </button>
                </form>
            @else
                <p class="mb-0 text-muted">Aksi approve/reject hanya tersedia untuk data dengan status Menunggu Verifikasi.</p>
            @endif
        </div>
    </section>
@endsection
