@extends('layouts.admin')

@section('title', 'Profil Saya | YAPISTA HRIS')

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
        $verificationStatuses = ['draft' => 'Draft', 'submitted' => 'Menunggu Verifikasi', 'verified' => 'Terverifikasi', 'rejected' => 'Ditolak'];
        $verificationClasses = ['draft' => 'bg-light-secondary text-secondary', 'submitted' => 'bg-light-primary text-primary', 'verified' => 'bg-light-success text-success', 'rejected' => 'bg-light-danger text-danger'];
        $genderLabels = ['male' => 'Laki-laki', 'female' => 'Perempuan'];
        $religionLabels = ['islam' => 'Islam', 'kristen' => 'Kristen', 'katolik' => 'Katolik', 'hindu' => 'Hindu', 'buddha' => 'Buddha', 'konghucu' => 'Konghucu'];
        $maritalLabels = ['single' => 'Belum Menikah', 'married' => 'Menikah', 'divorced' => 'Cerai Hidup', 'widowed' => 'Cerai Mati'];
        $certificationStatusClasses = ['active' => 'bg-light-success text-success', 'no_expiry' => 'bg-light-primary text-primary', 'expired' => 'bg-light-warning text-warning', 'inactive' => 'bg-light-secondary text-secondary'];
        $administrativeDetail = $employee->administrativeDetail;
        $taxStatusLabels = \App\Models\EmployeeAdministrativeDetail::TAX_STATUSES;
        $bpjsStatusLabels = \App\Models\EmployeeAdministrativeDetail::BPJS_STATUSES;
        $display = fn ($value) => filled($value) ? $value : 'Belum diisi';
        $familyCardNumber = $employee->family_card_number;
        $maskedFamilyCard = filled($familyCardNumber) ? str_repeat('*', max(strlen($familyCardNumber) - 4, 0)).substr($familyCardNumber, -4) : 'Belum diisi';
        $photoUrl = $employee->photo ? asset('storage/'.$employee->photo) : asset('assets/images/user/avatar-2.jpg');
        $ktpDocument = $employee->documents->firstWhere('document_type', 'ktp');
    @endphp

    <div class="page-header"><div class="page-block"><div class="row align-items-center"><div class="col-md-12"><ul class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('pegawai.dashboard') }}">Dashboard</a></li><li class="breadcrumb-item" aria-current="page">Profil Saya</li></ul></div></div></div></div>

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if ($employee->isSubmitted())<div class="alert alert-warning">Data Anda sedang menunggu verifikasi HR.</div>@endif
    @if ($employee->isVerified())<div class="alert alert-success">Data Anda sudah diverifikasi.</div>@endif

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div><h3 class="mb-1">Profil Saya</h3><p class="mb-0 text-muted">Kelola identitas, kontak, alamat, dan dokumen kepegawaian Anda.</p></div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('pegawai.profile.wizard.index') }}" class="btn btn-primary"><i class="ti ti-list-check"></i> Lengkapi Profil</a>
            <a href="{{ route('pegawai.documents.index') }}" class="btn btn-light-primary"><i class="ti ti-files"></i> Dokumen Saya</a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card"><div class="card-body text-center">
                <img src="{{ $photoUrl }}" alt="{{ $employee->full_name }}" class="rounded-circle wid-100 hei-100 mb-3" style="object-fit: cover;">
                <h4 class="mb-1">{{ $employee->full_name }}</h4>
                <p class="text-muted mb-2">NUP / Nomor Pegawai: {{ $employee->formatted_employee_number }}</p>
                <span class="badge {{ $verificationClasses[$employee->verification_status] ?? 'bg-light-secondary text-secondary' }}">{{ $verificationStatuses[$employee->verification_status] ?? $employee->verification_status }}</span>
            </div></div>
            <div class="card"><div class="card-header"><h5 class="mb-0">Kelengkapan Profil</h5></div><div class="card-body">
                <div class="d-flex justify-content-between mb-2"><span>Progress</span><strong>{{ $profileProgress['percentage'] }}%</strong></div>
                <div class="progress mb-3" style="height: 8px;" role="progressbar" aria-valuenow="{{ $profileProgress['percentage'] }}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" style="width: {{ $profileProgress['percentage'] }}%"></div></div>
                <p class="mb-1">{{ $profileProgress['completed_sections'] }} dari {{ $profileProgress['total_sections'] }} bagian data telah lengkap.</p>
                <small class="text-muted d-block mb-3">Belum termasuk pemeriksaan dokumen.</small>
                <a href="{{ route('pegawai.profile.wizard.index') }}" class="btn btn-outline-primary btn-sm w-100">Lengkapi Profil</a>
            </div></div>
            <div class="card"><div class="card-header"><h5 class="mb-0">Ringkasan Dokumen</h5></div><div class="card-body">
                <div class="d-flex justify-content-between mb-2"><span>Total dokumen</span><strong>{{ $employee->documents->count() }}</strong></div>
                <div class="d-flex justify-content-between"><span>Dokumen KTP</span><span class="badge {{ $ktpDocument ? 'bg-light-success text-success' : 'bg-light-danger text-danger' }}">{{ $ktpDocument ? 'Ada' : 'Belum ada' }}</span></div>
            </div></div>
        </div>

        <div class="col-lg-8">
            <div class="card"><div class="card-header"><h5 class="mb-0">Informasi Kepegawaian</h5></div><div class="card-body"><div class="row">
                <div class="col-md-6 mb-3"><small class="text-muted d-block">NUP / Nomor Pegawai</small>{{ $employee->formatted_employee_number }}</div>
                <div class="col-md-6 mb-3"><small class="text-muted d-block">Unit Kerja</small>{{ $employee->institution?->name ?? 'Belum diisi' }}</div>
                <div class="col-md-6 mb-3"><small class="text-muted d-block">Jabatan</small>{{ $employee->position?->name ?? 'Belum diisi' }}</div>
                <div class="col-md-6 mb-3"><small class="text-muted d-block">Jenis Pegawai</small>{{ $employeeTypes[$employee->employee_type] ?? $employee->employee_type }}</div>
                <div class="col-md-6 mb-3"><small class="text-muted d-block">Status Kepegawaian</small>{{ $employmentStatuses[$employee->employment_status] ?? $employee->employment_status }}</div>
                <div class="col-md-6 mb-3"><small class="text-muted d-block">Tanggal Masuk</small>{{ $employee->join_date?->format('d M Y') ?? 'Belum diisi' }}</div>
            </div></div></div>

            <div class="card"><div class="card-header"><h5 class="mb-0">Identitas Pribadi</h5></div><div class="card-body"><div class="row">
                <div class="col-md-6 mb-3"><small class="text-muted d-block">Nama Lengkap</small>{{ $employee->full_name }}</div>
                <div class="col-md-6 mb-3"><small class="text-muted d-block">NIK</small>{{ $display($employee->nik) }}</div>
                <div class="col-md-6 mb-3"><small class="text-muted d-block">Nomor Kartu Keluarga</small>{{ $maskedFamilyCard }}</div>
                <div class="col-md-6 mb-3"><small class="text-muted d-block">Jenis Kelamin</small>{{ $genderLabels[$employee->gender] ?? 'Belum diisi' }}</div>
                <div class="col-md-6 mb-3"><small class="text-muted d-block">Tempat Lahir</small>{{ $display($employee->birth_place) }}</div>
                <div class="col-md-6 mb-3"><small class="text-muted d-block">Tanggal Lahir</small>{{ $employee->birth_date?->format('d M Y') ?? 'Belum diisi' }}</div>
                <div class="col-md-6 mb-3"><small class="text-muted d-block">Agama</small>{{ $religionLabels[$employee->religion] ?? 'Belum diisi' }}</div>
                <div class="col-md-6 mb-3"><small class="text-muted d-block">Status Perkawinan</small>{{ $maritalLabels[$employee->marital_status] ?? 'Belum diisi' }}</div>
                <div class="col-md-6 mb-3"><small class="text-muted d-block">Kewarganegaraan</small>{{ $display($employee->nationality) }}</div>
                <div class="col-md-6 mb-3"><small class="text-muted d-block">Golongan Darah</small>{{ $display($employee->blood_type) }}</div>
            </div></div></div>

            <div class="card"><div class="card-header"><h5 class="mb-0">Kontak</h5></div><div class="card-body"><div class="row">
                <div class="col-md-6 mb-3"><small class="text-muted d-block">Nomor HP</small>{{ $display($employee->phone) }}</div>
                <div class="col-md-6 mb-3"><small class="text-muted d-block">Nomor WhatsApp</small>{{ $display($employee->whatsapp_number) }}</div>
                <div class="col-md-6 mb-3"><small class="text-muted d-block">Email Pribadi</small>{{ $display($employee->email) }}</div>
                <div class="col-md-6 mb-3"><small class="text-muted d-block">Email Login</small>{{ $display($employee->user?->email) }} <span class="badge bg-light-secondary text-secondary">Read-only</span></div>
            </div></div></div>

            <div class="card"><div class="card-header"><h5 class="mb-0">Alamat</h5></div><div class="card-body"><div class="row">
                <div class="col-12 mb-3"><small class="text-muted d-block">Alamat Sesuai KTP</small>{{ $display($employee->identity_address) }}</div>
                <div class="col-12 mb-3"><small class="text-muted d-block">Alamat Domisili</small>{{ $display($employee->address) }}</div>
                <div class="col-md-6 mb-3"><small class="text-muted d-block">Provinsi</small>{{ $display($employee->domicile_province) }}</div>
                <div class="col-md-6 mb-3"><small class="text-muted d-block">Kabupaten/Kota</small>{{ $display($employee->domicile_city) }}</div>
                <div class="col-md-6 mb-3"><small class="text-muted d-block">Kecamatan</small>{{ $display($employee->domicile_district) }}</div>
                <div class="col-md-6 mb-3"><small class="text-muted d-block">Kelurahan/Desa</small>{{ $display($employee->domicile_village) }}</div>
                <div class="col-md-6 mb-3"><small class="text-muted d-block">Kode Pos</small>{{ $display($employee->domicile_postal_code) }}</div>
                <div class="col-md-6 mb-3"><small class="text-muted d-block">Sama dengan Alamat KTP</small>{{ is_null($employee->domicile_same_as_identity) ? 'Belum diisi' : ($employee->domicile_same_as_identity ? 'Ya' : 'Tidak') }}</div>
            </div></div></div>

            <div class="card"><div class="card-header"><h5 class="mb-0">Kontak Darurat</h5></div><div class="card-body"><div class="row">
                <div class="col-md-6 mb-3"><small class="text-muted d-block">Nama Kontak</small>{{ $display($employee->emergency_contact_name) }}</div>
                <div class="col-md-6 mb-3"><small class="text-muted d-block">Hubungan</small>{{ $display($employee->emergency_contact_relationship) }}</div>
                <div class="col-md-6 mb-3"><small class="text-muted d-block">Nomor HP</small>{{ $display($employee->emergency_contact_phone) }}</div>
                <div class="col-12 mb-3"><small class="text-muted d-block">Alamat</small>{{ $display($employee->emergency_contact_address) }}</div>
            </div></div></div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div><h5 class="mb-1">Data Keluarga</h5><p class="mb-0 text-muted">Anggota keluarga dan status tanggungan Anda.</p></div>
                    @if ($employee->canEditProfile())
                        <a href="{{ route('pegawai.profile.family-members.create') }}" class="btn btn-primary btn-sm"><i class="ti ti-plus"></i> Tambah Anggota Keluarga</a>
                    @endif
                </div>
                <div class="card-body p-0">
                    @if ($employee->familyMembers->isEmpty())
                        <div class="text-center py-5 px-3">
                            <i class="ti ti-users fs-1 text-muted"></i>
                            <h6 class="mt-3 mb-1">Belum ada data keluarga</h6>
                            <p class="text-muted mb-0">Anggota keluarga dapat ditambahkan ketika datanya tersedia.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead><tr><th>Anggota Keluarga</th><th>Hubungan</th><th>Tanggal Lahir</th><th>Tanggungan</th><th>BPJS</th>@if ($employee->canEditProfile())<th class="text-end">Aksi</th>@endif</tr></thead>
                                <tbody>
                                    @foreach ($employee->familyMembers as $familyMember)
                                        <tr>
                                            <td><strong class="d-block">{{ $familyMember->full_name }}</strong><small class="text-muted">NIK: {{ $familyMember->masked_nik }}</small>@if ($familyMember->occupation)<small class="text-muted d-block">{{ $familyMember->occupation }}</small>@endif</td>
                                            <td>{{ $familyMember->relationship_label }}</td>
                                            <td>{{ $familyMember->birth_date?->format('d M Y') ?? 'Belum diisi' }}</td>
                                            <td><span class="badge {{ $familyMember->is_dependent ? 'bg-light-success text-success' : 'bg-light-secondary text-secondary' }}">{{ $familyMember->is_dependent ? 'Ya' : 'Tidak' }}</span></td>
                                            <td>{{ $familyMember->bpjs_status_label }}</td>
                                            @if ($employee->canEditProfile())
                                                <td><div class="d-flex justify-content-end gap-2">
                                                    <a href="{{ route('pegawai.profile.family-members.edit', $familyMember) }}" class="btn btn-sm btn-outline-primary"><i class="ti ti-edit"></i> Edit</a>
                                                    <form method="POST" action="{{ route('pegawai.profile.family-members.destroy', $familyMember) }}" onsubmit="return confirm('Hapus data anggota keluarga ini?')">@csrf @method('DELETE')<button type="submit" class="btn btn-sm btn-outline-danger"><i class="ti ti-trash"></i><span class="visually-hidden">Hapus</span></button></form>
                                                </div></td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div><h5 class="mb-1">Riwayat Pendidikan</h5><p class="mb-0 text-muted">Jenjang, institusi, dan gelar pendidikan formal Anda.</p></div>
                    @if ($employee->canEditProfile())
                        <a href="{{ route('pegawai.profile.educations.create') }}" class="btn btn-primary btn-sm"><i class="ti ti-plus"></i> Tambah Pendidikan</a>
                    @endif
                </div>
                <div class="card-body p-0">
                    @if ($employee->educations->isEmpty())
                        <div class="text-center py-5 px-3"><i class="ti ti-school fs-1 text-muted"></i><h6 class="mt-3 mb-1">Belum ada riwayat pendidikan yang ditambahkan</h6><p class="text-muted mb-0">Riwayat pendidikan dapat dilengkapi secara bertahap.</p></div>
                    @else
                        <div class="table-responsive"><table class="table table-hover align-middle mb-0">
                            <thead><tr><th>Pendidikan</th><th>Jurusan/Gelar</th><th>Tahun</th><th>Tertinggi</th>@if ($employee->canEditProfile())<th class="text-end">Aksi</th>@endif</tr></thead>
                            <tbody>
                                @foreach ($employee->educations as $education)
                                    <tr>
                                        <td><strong class="d-block">{{ $education->education_level_label }}</strong><span>{{ $education->institution_name }}</span><small class="text-muted d-block">Nomor ijazah: {{ $education->masked_certificate_number }}</small></td>
                                        <td>{{ $education->major ?? 'Belum diisi' }}@if ($education->degree_prefix || $education->degree_suffix)<small class="text-muted d-block">{{ trim(($education->degree_prefix ? $education->degree_prefix.' ' : '').$education->degree_suffix) }}</small>@endif</td>
                                        <td><small class="d-block">Masuk: {{ $education->start_year ?? 'Belum diisi' }}</small><small>Lulus: {{ $education->graduation_year ?? 'Belum diisi' }}</small></td>
                                        <td><span class="badge {{ $education->is_highest ? 'bg-light-success text-success' : 'bg-light-secondary text-secondary' }}">{{ $education->is_highest ? 'Ya' : 'Tidak' }}</span></td>
                                        @if ($employee->canEditProfile())<td><div class="d-flex justify-content-end gap-2"><a href="{{ route('pegawai.profile.educations.edit', $education) }}" class="btn btn-sm btn-outline-primary"><i class="ti ti-edit"></i> Edit</a><form method="POST" action="{{ route('pegawai.profile.educations.destroy', $education) }}" onsubmit="return confirm('Hapus data pendidikan ini?')">@csrf @method('DELETE')<button type="submit" class="btn btn-sm btn-outline-danger"><i class="ti ti-trash"></i><span class="visually-hidden">Hapus</span></button></form></div></td>@endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table></div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div><h5 class="mb-1">Sertifikasi dan Kompetensi</h5><p class="mb-0 text-muted">Sertifikasi profesional dan bidang kompetensi yang dimiliki.</p></div>
                    @if ($employee->canEditProfile())
                        <a href="{{ route('pegawai.profile.certifications.create') }}" class="btn btn-primary btn-sm"><i class="ti ti-plus"></i> Tambah Sertifikasi</a>
                    @endif
                </div>
                <div class="card-body p-0">
                    @if ($employee->certifications->isEmpty())
                        <div class="text-center py-5 px-3"><i class="ti ti-certificate fs-1 text-muted"></i><h6 class="mt-3 mb-1">Belum ada sertifikasi atau kompetensi yang ditambahkan</h6><p class="text-muted mb-0">Sertifikasi dapat ditambahkan ketika datanya tersedia.</p></div>
                    @else
                        <div class="table-responsive"><table class="table table-hover align-middle mb-0">
                            <thead><tr><th>Sertifikasi</th><th>Penerbit</th><th>Bidang</th><th>Masa Berlaku</th><th>Status</th>@if ($employee->canEditProfile())<th class="text-end">Aksi</th>@endif</tr></thead>
                            <tbody>
                                @foreach ($employee->certifications as $certification)
                                    <tr>
                                        <td><strong class="d-block">{{ $certification->name }}</strong><small class="text-muted">Nomor: {{ $certification->masked_certificate_number }}</small></td>
                                        <td>{{ $certification->issuer ?? 'Belum diisi' }}</td>
                                        <td>{{ $certification->competency_field ?? 'Belum diisi' }}</td>
                                        <td><small class="d-block">Terbit: {{ $certification->issued_at?->format('d M Y') ?? 'Belum diisi' }}</small><small>Kedaluwarsa: {{ $certification->expired_at?->format('d M Y') ?? 'Tidak ada' }}</small></td>
                                        <td><span class="badge {{ $certificationStatusClasses[$certification->effective_status] ?? 'bg-light-secondary text-secondary' }}">{{ $certification->effective_status_label }}</span></td>
                                        @if ($employee->canEditProfile())<td><div class="d-flex justify-content-end gap-2"><a href="{{ route('pegawai.profile.certifications.edit', $certification) }}" class="btn btn-sm btn-outline-primary"><i class="ti ti-edit"></i> Edit</a><form method="POST" action="{{ route('pegawai.profile.certifications.destroy', $certification) }}" onsubmit="return confirm('Hapus data sertifikasi ini?')">@csrf @method('DELETE')<button type="submit" class="btn btn-sm btn-outline-danger"><i class="ti ti-trash"></i><span class="visually-hidden">Hapus</span></button></form></div></td>@endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table></div>
                    @endif
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mt-4 mb-3">
                <div><h4 class="mb-1">Data Bank, Pajak, dan BPJS</h4><p class="mb-0 text-muted">Informasi sensitif ditampilkan dalam bentuk tersamarkan.</p></div>
                @if ($employee->canEditProfile())
                    <a href="{{ route('pegawai.profile.administrative-details.edit') }}" class="btn btn-primary btn-sm"><i class="ti ti-edit"></i> Edit Data Administrasi</a>
                @endif
            </div>

            <div class="row">
                <div class="col-md-6 col-xl-4">
                    <div class="card h-100"><div class="card-header"><h5 class="mb-0">Rekening Bank</h5></div><div class="card-body">
                        <div class="mb-3"><small class="text-muted d-block">Nama Bank</small>{{ $display($administrativeDetail?->bank_name) }}</div>
                        <div class="mb-3"><small class="text-muted d-block">Nomor Rekening</small>{{ $display($administrativeDetail?->masked_bank_account_number) }}</div>
                        <div><small class="text-muted d-block">Nama Pemilik Rekening</small>{{ $display($administrativeDetail?->bank_account_holder) }}</div>
                    </div></div>
                </div>
                <div class="col-md-6 col-xl-4">
                    <div class="card h-100"><div class="card-header"><h5 class="mb-0">Perpajakan</h5></div><div class="card-body">
                        <div class="mb-3"><small class="text-muted d-block">Status Pajak</small>{{ $administrativeDetail?->tax_status ? ($taxStatusLabels[$administrativeDetail->tax_status] ?? $administrativeDetail->tax_status) : 'Belum diisi' }}</div>
                        <div class="mb-3"><small class="text-muted d-block">Nomor Identitas Pajak</small>{{ $display($administrativeDetail?->masked_tax_identification_number) }}</div>
                        <div class="mb-3"><small class="text-muted d-block">NIK sebagai Identitas Pajak</small>{{ is_null($administrativeDetail?->nik_used_as_tax_id) ? 'Belum diisi' : ($administrativeDetail->nik_used_as_tax_id ? 'Ya' : 'Tidak') }}</div>
                        <div><small class="text-muted d-block">Status PTKP</small>{{ $display($administrativeDetail?->ptkp_status) }}</div>
                    </div></div>
                </div>
                <div class="col-md-12 col-xl-4">
                    <div class="card h-100"><div class="card-header"><h5 class="mb-0">BPJS</h5></div><div class="card-body">
                        <div class="mb-3"><small class="text-muted d-block">Status BPJS Kesehatan</small>{{ $administrativeDetail?->bpjs_health_status ? ($bpjsStatusLabels[$administrativeDetail->bpjs_health_status] ?? $administrativeDetail->bpjs_health_status) : 'Belum diisi' }}</div>
                        <div class="mb-3"><small class="text-muted d-block">Nomor BPJS Kesehatan</small>{{ $display($administrativeDetail?->masked_bpjs_health_number) }}</div>
                        <div class="mb-3"><small class="text-muted d-block">Status BPJS Ketenagakerjaan</small>{{ $administrativeDetail?->bpjs_employment_status ? ($bpjsStatusLabels[$administrativeDetail->bpjs_employment_status] ?? $administrativeDetail->bpjs_employment_status) : 'Belum diisi' }}</div>
                        <div><small class="text-muted d-block">Nomor KPJ/BPJS Ketenagakerjaan</small>{{ $display($administrativeDetail?->masked_bpjs_employment_number) }}</div>
                    </div></div>
                </div>
            </div>

            @if ($employee->isDraft() || $employee->isRejected())
                <div class="card"><div class="card-header"><h5 class="mb-0">Kirim untuk Verifikasi</h5></div><div class="card-body">
                    <p class="text-muted">Pengajuan menggunakan persyaratan verifikasi yang sudah berjalan. Simpan perubahan profil terlebih dahulu.</p>
                    <form method="POST" action="{{ route('pegawai.profile.submit') }}">@csrf<button type="submit" class="btn btn-success"><i class="ti ti-send"></i> Kirim untuk Verifikasi</button></form>
                </div></div>
            @endif
        </div>
    </div>
@endsection
