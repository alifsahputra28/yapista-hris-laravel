@extends('layouts.admin')

@section('title', 'Detail Pegawai | YAPISTA HRIS')

@section('content')
    @php
        $employeeTypes = [
            'guru' => 'Guru', 'dosen' => 'Dosen', 'tenaga_kependidikan' => 'Tenaga Kependidikan',
            'staff_yayasan' => 'Staff Yayasan', 'security' => 'Security', 'cleaning_service' => 'Cleaning Service',
            'driver' => 'Driver', 'teknisi' => 'Teknisi',
        ];
        $employmentStatuses = [
            'aktif' => 'Aktif', 'kontrak' => 'Kontrak', 'honorer' => 'Honorer',
            'part_time' => 'Part Time', 'nonaktif' => 'Nonaktif', 'resign' => 'Resign',
        ];
        $verificationStatuses = [
            'draft' => 'Belum Diverifikasi', 'submitted' => 'Menunggu Verifikasi',
            'verified' => 'Terverifikasi', 'rejected' => 'Ditolak',
        ];
        $profileReviewStatuses = [
            'draft' => 'Draft', 'submitted' => 'Menunggu Review',
            'approved' => 'Disetujui', 'rejected' => 'Perlu Perbaikan',
        ];
        $employmentClasses = [
            'aktif' => 'bg-light-success text-success', 'kontrak' => 'bg-light-primary text-primary',
            'honorer' => 'bg-light-warning text-warning', 'part_time' => 'bg-light-info text-info',
            'nonaktif' => 'bg-light-danger text-danger', 'resign' => 'bg-light-secondary text-secondary',
        ];
        $verificationClasses = [
            'draft' => 'bg-light-secondary text-secondary', 'submitted' => 'bg-light-primary text-primary',
            'verified' => 'bg-light-success text-success', 'rejected' => 'bg-light-danger text-danger',
        ];
        $profileReviewClasses = [
            'draft' => 'bg-light-secondary text-secondary', 'submitted' => 'bg-light-warning text-warning',
            'approved' => 'bg-light-success text-success', 'rejected' => 'bg-light-danger text-danger',
        ];
        $documentStatusClasses = [
            'pending' => 'bg-light-warning text-warning',
            'valid' => 'bg-light-success text-success',
            'rejected' => 'bg-light-danger text-danger',
        ];
        $genderLabels = ['male' => 'Laki-laki', 'female' => 'Perempuan'];
        $religionLabels = [
            'islam' => 'Islam', 'kristen' => 'Kristen', 'katolik' => 'Katolik',
            'hindu' => 'Hindu', 'buddha' => 'Buddha', 'konghucu' => 'Konghucu',
        ];
        $maritalLabels = [
            'single' => 'Belum Menikah', 'married' => 'Menikah',
            'divorced' => 'Cerai Hidup', 'widowed' => 'Cerai Mati',
        ];
        $display = fn ($value) => filled($value) ? $value : 'Belum diisi';
        $date = fn ($value, bool $withTime = false) => $value
            ? $value->locale('id')->translatedFormat($withTime ? 'd M Y H:i' : 'd M Y')
            : 'Belum diisi';
        $administrativeDetail = $employee->administrativeDetail;
        $photoUrl = $employee->photo
            ? route('employees.photo', $employee)
            : asset('assets/images/user/avatar-2.jpg');
    @endphp

    <x-page-header
        title="{{ $employee->full_name }}"
        subtitle="Master profil pegawai untuk kebutuhan review Admin dan HR."
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
                <img src="{{ $photoUrl }}" alt="Foto {{ $employee->full_name }}" class="rounded-circle object-fit-cover" width="48" height="48">
                <x-employee-context :employee="$employee" />
            </div>
        </x-slot:meta>
        <x-slot:actions>
            <a href="{{ route('employees.index') }}" class="btn btn-light-secondary">Kembali</a>
            <a href="{{ route('employees.id-card.show', $employee) }}" class="btn btn-light-primary">
                <i class="ti ti-id" aria-hidden="true"></i> ID Card
            </a>
            <a href="{{ route('employees.edit', $employee) }}" class="btn btn-primary">
                <i class="ti ti-edit" aria-hidden="true"></i> Edit Pegawai
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="row g-4">
        <div class="col-12 col-xl-6">
            <section class="content-section h-100 mb-0" aria-labelledby="basic-information-heading">
                <div class="content-section-header"><h2 id="basic-information-heading">Informasi Pribadi</h2></div>
                <div class="content-section-body detail-grid">
                    <div class="detail-item"><span class="detail-label">Nama Lengkap</span>{{ $display($employee->full_name) }}</div>
                    <div class="detail-item"><span class="detail-label">NIK</span>{{ $employee->masked_nik ?? 'Belum diisi' }}</div>
                    <div class="detail-item"><span class="detail-label">Nomor Kartu Keluarga</span>{{ $employee->masked_family_card_number ?? 'Belum diisi' }}</div>
                    <div class="detail-item"><span class="detail-label">Jenis Kelamin</span>{{ $genderLabels[$employee->gender] ?? 'Belum diisi' }}</div>
                    <div class="detail-item"><span class="detail-label">Tempat Lahir</span>{{ $display($employee->birth_place) }}</div>
                    <div class="detail-item"><span class="detail-label">Tanggal Lahir</span>{{ $date($employee->birth_date) }}</div>
                    <div class="detail-item"><span class="detail-label">Agama</span>{{ $religionLabels[$employee->religion] ?? 'Belum diisi' }}</div>
                    <div class="detail-item"><span class="detail-label">Status Perkawinan</span>{{ $maritalLabels[$employee->marital_status] ?? 'Belum diisi' }}</div>
                    <div class="detail-item"><span class="detail-label">Kewarganegaraan</span>{{ $display($employee->nationality) }}</div>
                    <div class="detail-item"><span class="detail-label">Golongan Darah</span>{{ $display($employee->blood_type) }}</div>
                </div>
            </section>
        </div>

        <div class="col-12 col-xl-6">
            <section class="content-section h-100 mb-0" aria-labelledby="employment-heading">
                <div class="content-section-header"><h2 id="employment-heading">Kepegawaian</h2></div>
                <div class="content-section-body detail-grid">
                    <div class="detail-item"><span class="detail-label">NUP / Nomor Pegawai</span>{{ $employee->formatted_employee_number }}</div>
                    <div class="detail-item"><span class="detail-label">Unit Kerja</span>{{ $employee->institution?->name ?? 'Belum diisi' }}</div>
                    <div class="detail-item"><span class="detail-label">Jabatan</span>{{ $employee->position?->name ?? 'Belum diisi' }}</div>
                    <div class="detail-item"><span class="detail-label">Jenis Pegawai</span>{{ $employeeTypes[$employee->employee_type] ?? $display($employee->employee_type) }}</div>
                    <div class="detail-item"><span class="detail-label">Status Kepegawaian</span><span class="badge {{ $employmentClasses[$employee->employment_status] ?? 'bg-light-secondary text-secondary' }}">{{ $employmentStatuses[$employee->employment_status] ?? $display($employee->employment_status) }}</span></div>
                    <div class="detail-item"><span class="detail-label">Tanggal Bergabung</span>{{ $date($employee->join_date) }}</div>
                </div>
            </section>
        </div>

        <div class="col-12 col-xl-6">
            <section class="content-section h-100 mb-0" aria-labelledby="contact-address-heading">
                <div class="content-section-header"><h2 id="contact-address-heading">Kontak &amp; Alamat</h2></div>
                <div class="content-section-body detail-grid">
                    <div class="detail-item"><span class="detail-label">Email Login</span>{{ $display($employee->user?->email) }}</div>
                    <div class="detail-item"><span class="detail-label">Email Pribadi</span>{{ $display($employee->email) }}</div>
                    <div class="detail-item"><span class="detail-label">Nomor HP</span>{{ $display($employee->phone) }}</div>
                    <div class="detail-item"><span class="detail-label">Nomor WhatsApp</span>{{ $display($employee->whatsapp_number) }}</div>
                    <div class="detail-item"><span class="detail-label">Alamat Sesuai KTP</span>{{ $display($employee->identity_address) }}</div>
                    <div class="detail-item"><span class="detail-label">Domisili Sama dengan KTP</span>{{ is_null($employee->domicile_same_as_identity) ? 'Belum diisi' : ($employee->domicile_same_as_identity ? 'Ya' : 'Tidak') }}</div>
                    <div class="detail-item"><span class="detail-label">Alamat Domisili</span>{{ $display($employee->address) }}</div>
                    <div class="detail-item"><span class="detail-label">Provinsi</span>{{ $display($employee->domicile_province) }}</div>
                    <div class="detail-item"><span class="detail-label">Kota/Kabupaten</span>{{ $display($employee->domicile_city) }}</div>
                    <div class="detail-item"><span class="detail-label">Kecamatan</span>{{ $display($employee->domicile_district) }}</div>
                    <div class="detail-item"><span class="detail-label">Kelurahan/Desa</span>{{ $display($employee->domicile_village) }}</div>
                    <div class="detail-item"><span class="detail-label">Kode Pos</span>{{ $display($employee->domicile_postal_code) }}</div>
                </div>
            </section>
        </div>

        <div class="col-12 col-xl-6">
            <section class="content-section h-100 mb-0" aria-labelledby="emergency-contact-heading">
                <div class="content-section-header"><h2 id="emergency-contact-heading">Kontak Darurat</h2></div>
                <div class="content-section-body detail-grid">
                    <div class="detail-item"><span class="detail-label">Nama Kontak</span>{{ $display($employee->emergency_contact_name) }}</div>
                    <div class="detail-item"><span class="detail-label">Hubungan</span>{{ $display($employee->emergency_contact_relationship) }}</div>
                    <div class="detail-item"><span class="detail-label">Nomor Kontak</span>{{ $display($employee->emergency_contact_phone) }}</div>
                    <div class="detail-item"><span class="detail-label">Alamat Kontak</span>{{ $display($employee->emergency_contact_address) }}</div>
                </div>
            </section>
        </div>

        <div class="col-12">
            <section class="content-section mb-0" aria-labelledby="family-heading">
                <div class="content-section-header"><div><h2 id="family-heading">Keluarga</h2><p>Anggota keluarga dan status tanggungan pegawai.</p></div></div>
                <div class="content-section-body p-0">
                    @if ($employee->familyMembers->isEmpty())
                        <div class="px-4 py-3 text-muted">Belum ada data keluarga.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead><tr><th>Nama</th><th>Hubungan</th><th>NIK</th><th>Jenis Kelamin</th><th>Tempat/Tanggal Lahir</th><th>Pekerjaan</th><th>Tanggungan</th><th>Status BPJS</th></tr></thead>
                                <tbody>
                                    @foreach ($employee->familyMembers as $familyMember)
                                        <tr>
                                            <td class="fw-semibold">{{ $familyMember->full_name }}</td>
                                            <td>{{ $familyMember->relationship_label }}</td>
                                            <td>{{ $familyMember->masked_nik }}</td>
                                            <td>{{ $genderLabels[$familyMember->gender] ?? 'Belum diisi' }}</td>
                                            <td>{{ $display($familyMember->birth_place) }} / {{ $date($familyMember->birth_date) }}</td>
                                            <td>{{ $display($familyMember->occupation) }}</td>
                                            <td>{{ $familyMember->is_dependent ? 'Ya' : 'Tidak' }}</td>
                                            <td>{{ $familyMember->bpjs_status_label }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </section>
        </div>

        <div class="col-12 col-xl-6">
            <section class="content-section h-100 mb-0" aria-labelledby="education-heading">
                <div class="content-section-header"><h2 id="education-heading">Pendidikan</h2></div>
                <div class="content-section-body p-0">
                    @forelse ($employee->educations as $education)
                        <div class="px-4 py-3 border-bottom">
                            <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-2">
                                <div><strong>{{ $education->education_level_label }}</strong><span class="text-muted"> &middot; {{ $education->institution_name }}</span></div>
                                @if ($education->is_highest)<span class="badge bg-light-primary text-primary">Pendidikan Tertinggi</span>@endif
                            </div>
                            <div class="row g-3 small">
                                <div class="col-sm-6"><span class="text-muted d-block">Jurusan</span>{{ $display($education->major) }}</div>
                                <div class="col-sm-6"><span class="text-muted d-block">Periode</span>{{ $display($education->start_year) }} - {{ $display($education->graduation_year) }}</div>
                                <div class="col-sm-6"><span class="text-muted d-block">Nomor Ijazah</span>{{ $education->masked_certificate_number }}</div>
                                <div class="col-sm-3"><span class="text-muted d-block">Gelar Depan</span>{{ $display($education->degree_prefix) }}</div>
                                <div class="col-sm-3"><span class="text-muted d-block">Gelar Belakang</span>{{ $display($education->degree_suffix) }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="px-4 py-3 text-muted">Belum ada data pendidikan.</div>
                    @endforelse
                </div>
            </section>
        </div>

        <div class="col-12 col-xl-6">
            <section class="content-section h-100 mb-0" aria-labelledby="certification-heading">
                <div class="content-section-header"><h2 id="certification-heading">Sertifikasi</h2></div>
                <div class="content-section-body p-0">
                    @forelse ($employee->certifications as $certification)
                        <div class="px-4 py-3 border-bottom">
                            <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-2">
                                <strong>{{ $certification->name }}</strong>
                                <span class="badge bg-light-secondary text-secondary">{{ $certification->effective_status_label }}</span>
                            </div>
                            <div class="row g-3 small">
                                <div class="col-sm-6"><span class="text-muted d-block">Nomor Sertifikat</span>{{ $certification->masked_certificate_number }}</div>
                                <div class="col-sm-6"><span class="text-muted d-block">Penerbit</span>{{ $display($certification->issuer) }}</div>
                                <div class="col-sm-6"><span class="text-muted d-block">Bidang Kompetensi</span>{{ $display($certification->competency_field) }}</div>
                                <div class="col-sm-3"><span class="text-muted d-block">Tanggal Terbit</span>{{ $date($certification->issued_at) }}</div>
                                <div class="col-sm-3"><span class="text-muted d-block">Kedaluwarsa</span>{{ $date($certification->expired_at) }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="px-4 py-3 text-muted">Belum ada data sertifikasi.</div>
                    @endforelse
                </div>
            </section>
        </div>

        <div class="col-12">
            <section class="content-section mb-0" aria-labelledby="administration-heading">
                <div class="content-section-header"><div><h2 id="administration-heading">Administrasi, Jaminan &amp; Rekening Bank</h2><p>Nomor sensitif selalu ditampilkan dalam bentuk tersamarkan.</p></div></div>
                <div class="content-section-body detail-grid">
                    <div class="detail-item"><span class="detail-label">Nama Bank</span>{{ $display($administrativeDetail?->bank_name) }}</div>
                    <div class="detail-item"><span class="detail-label">Nomor Rekening</span>{{ $display($administrativeDetail?->masked_bank_account_number) }}</div>
                    <div class="detail-item"><span class="detail-label">Nama Pemilik Rekening</span>{{ $display($administrativeDetail?->bank_account_holder) }}</div>
                    <div class="detail-item"><span class="detail-label">Status Pajak</span>{{ \App\Models\EmployeeAdministrativeDetail::TAX_STATUSES[$administrativeDetail?->tax_status] ?? 'Belum diisi' }}</div>
                    <div class="detail-item"><span class="detail-label">Nomor Identitas Pajak</span>{{ $display($administrativeDetail?->masked_tax_identification_number) }}</div>
                    <div class="detail-item"><span class="detail-label">NIK sebagai Identitas Pajak</span>{{ is_null($administrativeDetail?->nik_used_as_tax_id) ? 'Belum diisi' : ($administrativeDetail->nik_used_as_tax_id ? 'Ya' : 'Tidak') }}</div>
                    <div class="detail-item"><span class="detail-label">Status PTKP</span>{{ $display($administrativeDetail?->ptkp_status) }}</div>
                    <div class="detail-item"><span class="detail-label">Status BPJS Kesehatan</span>{{ \App\Models\EmployeeAdministrativeDetail::BPJS_STATUSES[$administrativeDetail?->bpjs_health_status] ?? 'Belum diisi' }}</div>
                    <div class="detail-item"><span class="detail-label">Nomor BPJS Kesehatan</span>{{ $display($administrativeDetail?->masked_bpjs_health_number) }}</div>
                    <div class="detail-item"><span class="detail-label">Status BPJS Ketenagakerjaan</span>{{ \App\Models\EmployeeAdministrativeDetail::BPJS_STATUSES[$administrativeDetail?->bpjs_employment_status] ?? 'Belum diisi' }}</div>
                    <div class="detail-item"><span class="detail-label">Nomor BPJS Ketenagakerjaan</span>{{ $display($administrativeDetail?->masked_bpjs_employment_number) }}</div>
                </div>
            </section>
        </div>

        <div class="col-12">
            <section class="content-section mb-0" aria-labelledby="documents-heading">
                <div class="content-section-header"><h2 id="documents-heading">Dokumen Pegawai</h2></div>
                <div class="content-section-body p-0">
                    @if ($employee->documents->isEmpty())
                        <div class="px-4 py-3 text-muted">Belum ada dokumen pegawai.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead><tr><th>Jenis Dokumen</th><th>Nama File</th><th>Status</th><th>Tanggal Upload</th><th>Catatan</th><th class="text-end">File</th></tr></thead>
                                <tbody>
                                    @foreach ($employee->documents as $document)
                                        <tr>
                                            <td>{{ $document->document_type_label }}</td>
                                            <td>{{ $display($document->original_name) }}</td>
                                            <td><span class="badge {{ $documentStatusClasses[$document->status] ?? 'bg-light-secondary text-secondary' }}">{{ ['pending' => 'Menunggu', 'valid' => 'Valid', 'rejected' => 'Ditolak'][$document->status] ?? $document->status }}</span></td>
                                            <td>{{ $date($document->uploaded_at, true) }}</td>
                                            <td>{{ $display($document->note) }}</td>
                                            <td class="text-end">
                                                <div class="table-actions">
                                                    <a href="{{ route('employee-documents.view', $document) }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-light-primary"><i class="ti ti-eye" aria-hidden="true"></i> Lihat</a>
                                                    <a href="{{ route('employee-documents.download', $document) }}" class="btn btn-sm btn-light-secondary" aria-label="Download {{ $document->document_type_label }}"><i class="ti ti-download" aria-hidden="true"></i></a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </section>
        </div>

        <div class="col-12 col-xl-6">
            <section class="content-section h-100 mb-0" aria-labelledby="verification-heading">
                <div class="content-section-header"><h2 id="verification-heading">Verifikasi</h2></div>
                <div class="content-section-body detail-grid">
                    <div class="detail-item"><span class="detail-label">Status Verifikasi</span><span class="badge {{ $verificationClasses[$employee->verification_status] ?? 'bg-light-secondary text-secondary' }}">{{ $verificationStatuses[$employee->verification_status] ?? $employee->verification_status }}</span></div>
                    <div class="detail-item"><span class="detail-label">Diverifikasi Oleh</span>{{ $employee->verifier?->name ?? 'Belum tersedia' }}</div>
                    <div class="detail-item"><span class="detail-label">Waktu Verifikasi</span>{{ $employee->verified_at ? $date($employee->verified_at, true) : 'Belum tersedia' }}</div>
                    <div class="detail-item"><span class="detail-label">Catatan Verifikasi</span>{{ $display($employee->verification_note) }}</div>
                    <div class="detail-item"><span class="detail-label">Status Review Profil</span><span class="badge {{ $profileReviewClasses[$employee->profile_review_status] ?? 'bg-light-secondary text-secondary' }}">{{ $profileReviewStatuses[$employee->profile_review_status] ?? $display($employee->profile_review_status) }}</span></div>
                    <div class="detail-item"><span class="detail-label">Direview Oleh</span>{{ $employee->profileReviewer?->name ?? 'Belum tersedia' }}</div>
                    <div class="detail-item"><span class="detail-label">Waktu Pengajuan Profil</span>{{ $employee->profile_submitted_at ? $date($employee->profile_submitted_at, true) : 'Belum tersedia' }}</div>
                    <div class="detail-item"><span class="detail-label">Waktu Review Profil</span>{{ $employee->profile_reviewed_at ? $date($employee->profile_reviewed_at, true) : 'Belum tersedia' }}</div>
                    <div class="detail-item"><span class="detail-label">Catatan Review Profil</span>{{ $display($employee->profile_review_note) }}</div>
                </div>
            </section>
        </div>

        <div class="col-12 col-xl-6">
            <section class="content-section h-100 mb-0" aria-labelledby="profile-completion-heading">
                <div class="content-section-header"><div><h2 id="profile-completion-heading">Kelengkapan Profil</h2><p>{{ $employee->isVerified() ? 'Data tambahan bersifat opsional untuk pegawai existing yang telah terverifikasi.' : 'Menggunakan perhitungan yang sama dengan profile wizard.' }}</p></div></div>
                <div class="content-section-body">
                    <div class="d-flex align-items-center justify-content-between gap-3 mb-2"><span class="fw-semibold">Progress</span><strong>{{ $profileProgress['percentage'] }}%</strong></div>
                    <div class="progress profile-progress mb-3" role="progressbar" aria-label="Kelengkapan profil" aria-valuenow="{{ $profileProgress['percentage'] }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar" style="width: {{ $profileProgress['percentage'] }}%"></div>
                    </div>
                    <div class="list-group list-group-flush border-top">
                        @foreach ($profileProgress['sections'] as $section)
                            <div class="list-group-item px-0 d-flex align-items-center justify-content-between gap-3">
                                <span>{{ $section['label'] }}</span>
                                <span class="badge {{ $section['completed'] ? 'bg-light-success text-success' : 'bg-light-secondary text-secondary' }}">{{ $section['percentage'] }}%</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
