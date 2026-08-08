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

    <x-page-header
        title="{{ $employee->full_name }}"
        subtitle="Detail biodata, data kepegawaian, verifikasi, dan dokumen."
        :breadcrumbs="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Data Pegawai', 'url' => route('employees.index')],
            ['label' => 'Detail'],
        ]"
        :badge-label="$verificationStatuses[$employee->verification_status] ?? $employee->verification_status"
        :badge-class="$verificationClasses[$employee->verification_status] ?? 'bg-light-secondary text-secondary'"
    >
        <x-slot:meta>
            <div class="d-flex align-items-center gap-3">
                <img src="{{ $photoUrl }}" alt="Foto {{ $employee->full_name }}" class="rounded-circle" width="48" height="48" style="object-fit: cover;">
                <x-employee-context :employee="$employee" />
            </div>
        </x-slot:meta>
        <x-slot:actions>
            <a href="{{ route('employees.index') }}" class="btn btn-light-secondary">Kembali</a>
            <a href="{{ route('employees.id-card.show', $employee) }}" class="btn btn-light-primary">
                <i class="ti ti-id"></i> ID Card
            </a>
            <a href="{{ route('employees.edit', $employee) }}" class="btn btn-primary">
                <i class="ti ti-edit"></i> Edit
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="row g-4">
        <div class="col-xl-7">
            <section class="content-section">
                <div class="content-section-header">
                    <h2>Informasi Dasar</h2>
                </div>
                <div class="content-section-body detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">Nama Lengkap</span>
                            {{ $employee->full_name }}
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">NIK</span>
                            {{ $employee->masked_nik ?? 'Belum diisi' }}
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Jenis Kelamin</span>
                            {{ $employee->gender === 'male' ? 'Laki-laki' : ($employee->gender === 'female' ? 'Perempuan' : 'Belum diisi') }}
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Tempat, Tanggal Lahir</span>
                            {{ $employee->birth_place ?? 'Belum diisi' }}{{ $employee->birth_date ? ', '.$employee->birth_date->format('d M Y') : '' }}
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Jenis Pegawai</span>
                            {{ $employeeTypes[$employee->employee_type] ?? $employee->employee_type }}
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Tanggal Masuk</span>
                            {{ $employee->join_date?->format('d M Y') ?? 'Belum diisi' }}
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">NUP / Nomor Pegawai</span>
                            {{ $employee->formatted_employee_number }}
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Status Kepegawaian</span>
                            <span class="badge {{ $employmentClasses[$employee->employment_status] ?? 'bg-light-secondary text-secondary' }}">
                                {{ $employmentStatuses[$employee->employment_status] ?? $employee->employment_status }}
                            </span>
                        </div>
                </div>
            </section>

            <section class="content-section">
                <div class="content-section-header">
                    <h2>Unit dan Jabatan</h2>
                </div>
                <div class="content-section-body detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">Unit Kerja</span>
                            {{ $employee->institution?->name ?? 'Belum diisi' }}
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Jabatan</span>
                            {{ $employee->position?->name ?? 'Belum diisi' }}
                        </div>
                </div>
            </section>

            <section class="content-section">
                <div class="content-section-header">
                    <h2>Kontak dan Verifikasi</h2>
                </div>
                <div class="content-section-body detail-grid">
                        <div class="detail-item">
                            <span class="detail-label">Email</span>
                            {{ $employee->email ?? 'Belum diisi' }}
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Nomor HP</span>
                            {{ $employee->phone ?? 'Belum diisi' }}
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Alamat</span>
                            {{ $employee->address ?? 'Belum diisi' }}
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Status Verifikasi</span>
                            <span class="badge {{ $verificationClasses[$employee->verification_status] ?? 'bg-light-secondary text-secondary' }}">
                                {{ $verificationStatuses[$employee->verification_status] ?? $employee->verification_status }}
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Diverifikasi Oleh</span>
                            {{ $employee->verifier?->name ?? 'Belum diisi' }}
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Catatan Verifikasi</span>
                            {{ $employee->verification_note ?? 'Belum diisi' }}
                        </div>
                </div>
            </section>
        </div>

        <div class="col-xl-5">
            <section class="content-section h-100 mb-0">
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
                                                <a href="{{ route('employee-documents.view', $document) }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-light-primary">
                                                    <i class="ti ti-eye"></i>
                                                    Lihat
                                                </a>
                                                <a href="{{ route('employee-documents.download', $document) }}" class="btn btn-sm btn-light-secondary" title="Download dokumen">
                                                    <i class="ti ti-download"></i>
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
            </section>
        </div>
    </div>
@endsection
