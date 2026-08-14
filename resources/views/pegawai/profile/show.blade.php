@extends('layouts.admin')

@section('title', 'Akun | YAPISTA HRIS')

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
        $verificationStatuses = ['draft' => 'Draft', 'submitted' => 'Menunggu Verifikasi', 'verified' => 'Terverifikasi', 'rejected' => 'Perlu Perbaikan'];
        $verificationClasses = ['draft' => 'bg-light-secondary text-secondary', 'submitted' => 'bg-light-warning text-warning', 'verified' => 'bg-light-success text-success', 'rejected' => 'bg-light-danger text-danger'];
        $genderLabels = ['male' => 'Laki-laki', 'female' => 'Perempuan'];
        $religionLabels = ['islam' => 'Islam', 'kristen' => 'Kristen', 'katolik' => 'Katolik', 'hindu' => 'Hindu', 'buddha' => 'Buddha', 'konghucu' => 'Konghucu'];
        $maritalLabels = ['single' => 'Belum Menikah', 'married' => 'Menikah', 'divorced' => 'Cerai Hidup', 'widowed' => 'Cerai Mati'];
        $administrativeDetail = $employee->administrativeDetail;
        $display = fn ($value) => filled($value) ? $value : 'Belum diisi';
        $familyCardNumber = $employee->family_card_number;
        $maskedFamilyCard = filled($familyCardNumber) ? str_repeat('*', max(strlen($familyCardNumber) - 4, 0)).substr($familyCardNumber, -4) : 'Belum diisi';
        $accountPhoto = $employee->photo ? route('employees.photo', $employee) : asset('assets/images/user/avatar-2.jpg');
    @endphp

    <div class="d-lg-none">
        <h1 class="h4 mb-3">Akun</h1>

        @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
        @if ($employee->isSubmitted())
            <div class="alert alert-warning">Data sedang diperiksa HR dan sementara tidak dapat diubah.</div>
        @elseif ($employee->verification_status === 'rejected' && filled($employee->verification_note))
            <div class="alert alert-danger"><strong>Perlu perbaikan:</strong> {{ $employee->verification_note }}</div>
        @endif

        <div class="d-flex align-items-center gap-3 p-3 mb-3 border rounded bg-white">
            <img src="{{ $accountPhoto }}" alt="Foto {{ $employee->full_name }}" class="rounded-circle wid-60 hei-60 object-fit-cover flex-shrink-0">
            <div>
                <strong class="d-block">{{ $employee->full_name }}</strong>
                <span class="text-muted small">NUP {{ $employee->formatted_employee_number }}</span>
            </div>
        </div>

        <div class="list-group list-group-flush border-top border-bottom bg-white mb-3">
            <a href="{{ route('pegawai.profile.wizard.show', 'identification') }}" class="list-group-item list-group-item-action px-3 py-3">
                <div class="d-flex align-items-center justify-content-between gap-3"><div class="d-flex align-items-center gap-3"><i class="ti ti-user text-primary f-20" aria-hidden="true"></i><span>Data Pribadi</span></div><i class="ti ti-chevron-right text-muted" aria-hidden="true"></i></div>
            </a>
            <button type="button" class="list-group-item list-group-item-action px-3 py-3" data-bs-toggle="collapse" data-bs-target="#mobile-employment-information" aria-expanded="false" aria-controls="mobile-employment-information">
                <span class="d-flex align-items-center justify-content-between gap-3"><span class="d-flex align-items-center gap-3"><i class="ti ti-briefcase text-primary f-20" aria-hidden="true"></i><span>Informasi Kepegawaian</span></span><i class="ti ti-chevron-down text-muted" aria-hidden="true"></i></span>
            </button>
            <a href="{{ route('profile.edit') }}" class="list-group-item list-group-item-action px-3 py-3">
                <div class="d-flex align-items-center justify-content-between gap-3"><div class="d-flex align-items-center gap-3"><i class="ti ti-shield-lock text-primary f-20" aria-hidden="true"></i><span>Keamanan Akun</span></div><i class="ti ti-chevron-right text-muted" aria-hidden="true"></i></div>
            </a>
            <a href="{{ route('profile.edit') }}#password-heading" class="list-group-item list-group-item-action px-3 py-3">
                <div class="d-flex align-items-center justify-content-between gap-3"><div class="d-flex align-items-center gap-3"><i class="ti ti-key text-primary f-20" aria-hidden="true"></i><span>Ubah Password</span></div><i class="ti ti-chevron-right text-muted" aria-hidden="true"></i></div>
            </a>
        </div>

        <div class="collapse mb-3" id="mobile-employment-information">
            <div class="border rounded bg-white px-3">
                @foreach ([
                    'NUP' => $employee->formatted_employee_number,
                    'Unit' => $employee->institution?->name ?? 'Belum ditetapkan',
                    'Jabatan' => $employee->position?->name ?? 'Belum ditetapkan',
                    'Jenis Pegawai' => $employeeTypes[$employee->employee_type] ?? $employee->employee_type,
                    'Status Kerja' => $employmentStatuses[$employee->employment_status] ?? $employee->employment_status,
                    'Tanggal Masuk' => $employee->join_date?->locale('id')->translatedFormat('d M Y') ?? 'Belum diisi',
                ] as $label => $value)
                    <div class="d-flex align-items-start justify-content-between gap-3 py-3 border-bottom">
                        <span class="text-muted small">{{ $label }}</span><strong class="small text-end">{{ $value }}</strong>
                    </div>
                @endforeach
            </div>
        </div>

        <a href="{{ route('pegawai.profile.wizard.index') }}" class="d-flex align-items-center justify-content-between gap-3 p-3 mb-3 border rounded bg-white text-body text-decoration-none">
            <div><strong class="d-block">Data tambahan</strong><span class="text-muted small">Opsional &bull; perbarui jika diperlukan</span></div><i class="ti ti-chevron-right text-muted" aria-hidden="true"></i>
        </a>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-light-danger w-100"><i class="ti ti-logout me-1" aria-hidden="true"></i> Keluar</button>
        </form>
    </div>

    <div class="d-none d-lg-block">

    <x-page-header
        title="Akun"
        subtitle="Data pribadi dan informasi kepegawaian Anda."
        :badge-label="$verificationStatuses[$employee->verification_status] ?? $employee->verification_status"
        :badge-class="$verificationClasses[$employee->verification_status] ?? 'bg-light-secondary text-secondary'"
        :breadcrumbs="[
            ['label' => 'Beranda', 'url' => route('pegawai.dashboard')],
            ['label' => 'Akun'],
        ]"
    >
        <x-slot:actions>
            <a href="{{ route('pegawai.profile.wizard.index') }}" class="btn btn-primary">
                <i class="ti ti-edit" aria-hidden="true"></i>
                {{ $employee->canEditProfileCompletion() ? 'Perbarui Data' : 'Lihat Data Profil' }}
            </a>
        </x-slot:actions>
    </x-page-header>

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if ($employee->isSubmitted())
        <div class="alert alert-warning">Profil sedang diperiksa HR dan sementara tidak dapat diubah.</div>
    @elseif ($employee->verification_status === 'rejected' && filled($employee->verification_note))
        <div class="alert alert-danger"><strong>Perlu perbaikan:</strong> {{ $employee->verification_note }}</div>
    @endif

    @if ($employee->isVerified())
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 border-bottom pb-3 mb-4">
            <div>
                <span class="fw-semibold">Data tambahan</span>
                <span class="text-muted small ms-1">Opsional</span>
            </div>
            <span class="text-muted small">{{ $profileProgress['percentage'] }}% terisi</span>
        </div>
    @else
        <div class="d-flex justify-content-between gap-3 mb-2">
            <span class="fw-semibold">Kelengkapan Profil</span>
            <span>{{ $profileProgress['percentage'] }}% terisi</span>
        </div>
        <div class="progress profile-progress" role="progressbar" aria-label="Kelengkapan profil" aria-valuenow="{{ $profileProgress['percentage'] }}" aria-valuemin="0" aria-valuemax="100">
            <div class="progress-bar" style="width: {{ $profileProgress['percentage'] }}%"></div>
        </div>
    @endif

    <section class="content-section" aria-labelledby="employment-heading">
        <div class="content-section-header"><h2 id="employment-heading">Informasi Kepegawaian</h2></div>
        <div class="content-section-body detail-grid">
            <div class="detail-item"><span class="detail-label">NUP / Nomor Pegawai</span>{{ $employee->formatted_employee_number }}</div>
            <div class="detail-item"><span class="detail-label">Unit Kerja</span>{{ $employee->institution?->name ?? 'Belum ditetapkan' }}</div>
            <div class="detail-item"><span class="detail-label">Jabatan</span>{{ $employee->position?->name ?? 'Belum ditetapkan' }}</div>
            <div class="detail-item"><span class="detail-label">Jenis Pegawai</span>{{ $employeeTypes[$employee->employee_type] ?? $employee->employee_type }}</div>
            <div class="detail-item"><span class="detail-label">Status Kepegawaian</span>{{ $employmentStatuses[$employee->employment_status] ?? $employee->employment_status }}</div>
        </div>
    </section>

    <section class="content-section" aria-labelledby="identity-heading">
        <div class="content-section-header"><h2 id="identity-heading">Identitas dan Kontak</h2></div>
        <div class="content-section-body detail-grid">
            <div class="detail-item"><span class="detail-label">Nama Lengkap</span>{{ $employee->full_name }}</div>
            <div class="detail-item"><span class="detail-label">NIK</span>{{ $employee->masked_nik ?? 'Belum diisi' }}</div>
            <div class="detail-item"><span class="detail-label">Nomor Kartu Keluarga</span>{{ $maskedFamilyCard }}</div>
            <div class="detail-item"><span class="detail-label">Tempat, Tanggal Lahir</span>{{ $display($employee->birth_place) }}, {{ $employee->birth_date?->locale('id')->translatedFormat('d M Y') ?? 'Belum diisi' }}</div>
            <div class="detail-item"><span class="detail-label">Jenis Kelamin</span>{{ $genderLabels[$employee->gender] ?? 'Belum diisi' }}</div>
            <div class="detail-item"><span class="detail-label">Agama</span>{{ $religionLabels[$employee->religion] ?? 'Belum diisi' }}</div>
            <div class="detail-item"><span class="detail-label">Status Perkawinan</span>{{ $maritalLabels[$employee->marital_status] ?? 'Belum diisi' }}</div>
            <div class="detail-item"><span class="detail-label">Nomor HP / WhatsApp</span>{{ $display($employee->phone) }} / {{ $display($employee->whatsapp_number) }}</div>
            <div class="detail-item"><span class="detail-label">Email Pribadi</span>{{ $display($employee->email) }}</div>
            <div class="detail-item"><span class="detail-label">Alamat Sesuai KTP</span>{{ $display($employee->identity_address) }}</div>
            <div class="detail-item"><span class="detail-label">Alamat Domisili</span>{{ $display($employee->address) }}</div>
            <div class="detail-item"><span class="detail-label">Kontak Darurat</span>{{ $display($employee->emergency_contact_name) }}</div>
            <div class="detail-item"><span class="detail-label">Nomor Kontak Darurat</span>{{ $display($employee->emergency_contact_phone) }}</div>
        </div>
    </section>

    <section class="content-section" aria-labelledby="family-heading">
        <div class="content-section-header">
            <div><h2 id="family-heading">Keluarga</h2><p>Anggota keluarga dan status tanggungan.</p></div>
            @if ($employee->canEditProfileCompletion())
                <a href="{{ route('pegawai.profile.family-members.create') }}" class="btn btn-sm btn-primary"><i class="ti ti-plus" aria-hidden="true"></i> Tambah</a>
            @endif
        </div>
        <div class="content-section-body p-0">
            @if ($employee->familyMembers->isEmpty())
                <div class="empty-state"><h6 class="mb-1">Belum ada data keluarga</h6><p class="text-muted mb-0">Bagian ini dapat dilengkapi saat datanya tersedia.</p></div>
            @else
                <div class="table-responsive"><table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Nama</th><th>Hubungan</th><th>Tanggal Lahir</th><th>Tanggungan</th>@if ($employee->canEditProfileCompletion())<th class="text-end">Aksi</th>@endif</tr></thead>
                    <tbody>@foreach ($employee->familyMembers as $familyMember)<tr>
                        <td><strong>{{ $familyMember->full_name }}</strong><small class="data-meta d-block">NIK {{ $familyMember->masked_nik }}</small></td>
                        <td>{{ $familyMember->relationship_label }}</td><td>{{ $familyMember->birth_date?->locale('id')->translatedFormat('d M Y') ?? '-' }}</td><td>{{ $familyMember->is_dependent ? 'Ya' : 'Tidak' }}</td>
                        @if ($employee->canEditProfileCompletion())<td class="text-end"><div class="table-actions"><a href="{{ route('pegawai.profile.family-members.edit', $familyMember) }}" class="btn btn-sm btn-light-primary"><i class="ti ti-edit" aria-hidden="true"></i> Edit</a><form method="POST" action="{{ route('pegawai.profile.family-members.destroy', $familyMember) }}" data-confirm-title="Hapus Anggota Keluarga?" data-confirm-message="Data anggota keluarga ini akan dihapus. Lanjutkan?">@csrf @method('DELETE')<button type="submit" class="btn btn-sm btn-light-danger" aria-label="Hapus {{ $familyMember->full_name }}"><i class="ti ti-trash" aria-hidden="true"></i></button></form></div></td>@endif
                    </tr>@endforeach</tbody>
                </table></div>
            @endif
        </div>
    </section>

    <section class="content-section" aria-labelledby="education-heading">
        <div class="content-section-header">
            <div><h2 id="education-heading">Pendidikan dan Kompetensi</h2><p>Riwayat pendidikan serta sertifikasi profesional.</p></div>
            @if ($employee->canEditProfileCompletion())
                <div class="d-flex gap-2"><a href="{{ route('pegawai.profile.educations.create') }}" class="btn btn-sm btn-light-primary">Tambah Pendidikan</a><a href="{{ route('pegawai.profile.certifications.create') }}" class="btn btn-sm btn-light-primary">Tambah Sertifikasi</a></div>
            @endif
        </div>
        <div class="content-section-body table-responsive">
            <h3 class="h6 mb-3">Riwayat Pendidikan</h3>
            @forelse ($employee->educations as $education)
                <div class="d-flex justify-content-between align-items-start gap-3 py-2 border-bottom"><div><strong>{{ $education->education_level_label }} - {{ $education->institution_name }}</strong><div class="data-meta">{{ $education->major ?? 'Jurusan belum diisi' }} &bull; Lulus {{ $education->graduation_year ?? '-' }} &bull; No. ijazah {{ $education->masked_certificate_number ?? '-' }}</div></div>@if ($employee->canEditProfileCompletion())<a href="{{ route('pegawai.profile.educations.edit', $education) }}" class="btn btn-sm btn-light">Edit</a>@endif</div>
            @empty
                <p class="text-muted">Belum ada riwayat pendidikan yang ditambahkan.</p>
            @endforelse
            <h3 class="h6 mt-4 mb-3">Sertifikasi</h3>
            @forelse ($employee->certifications as $certification)
                <div class="d-flex justify-content-between align-items-start gap-3 py-2 border-bottom"><div><strong>{{ $certification->name }}</strong><div class="data-meta">{{ $certification->issuer ?? 'Penerbit belum diisi' }} &bull; {{ $certification->effective_status_label }} &bull; No. sertifikat {{ $certification->masked_certificate_number ?? '-' }}</div></div>@if ($employee->canEditProfileCompletion())<a href="{{ route('pegawai.profile.certifications.edit', $certification) }}" class="btn btn-sm btn-light">Edit</a>@endif</div>
            @empty
                <p class="text-muted mb-0">Belum ada sertifikasi atau kompetensi yang ditambahkan.</p>
            @endforelse
        </div>
    </section>

    <section class="content-section" aria-labelledby="administration-heading">
        <div class="content-section-header">
            <div><h2 id="administration-heading">Data Bank, Pajak, dan BPJS</h2><p>Informasi administratif ditampilkan tersamarkan.</p></div>
            @if ($employee->canEditProfileCompletion())<a href="{{ route('pegawai.profile.administrative-details.edit') }}" class="btn btn-sm btn-primary"><i class="ti ti-edit" aria-hidden="true"></i> Edit Data Administrasi</a>@endif
        </div>
        <div class="content-section-body detail-grid">
            <div class="detail-item"><span class="detail-label">Bank</span>{{ $display($administrativeDetail?->bank_name) }}</div>
            <div class="detail-item"><span class="detail-label">Nomor Rekening</span>{{ $display($administrativeDetail?->masked_bank_account_number) }}</div>
            <div class="detail-item"><span class="detail-label">Identitas Pajak</span>{{ $display($administrativeDetail?->masked_tax_identification_number) }}</div>
            <div class="detail-item"><span class="detail-label">Status PTKP</span>{{ $display($administrativeDetail?->ptkp_status) }}</div>
            <div class="detail-item"><span class="detail-label">BPJS Kesehatan</span>{{ $display($administrativeDetail?->masked_bpjs_health_number) }}</div>
            <div class="detail-item"><span class="detail-label">BPJS Ketenagakerjaan</span>{{ $display($administrativeDetail?->masked_bpjs_employment_number) }}</div>
        </div>
    </section>

    <section class="content-section" aria-labelledby="security-heading">
        <div class="content-section-header">
            <div>
                <h2 id="security-heading">Keamanan Akun</h2>
                <p>Kelola email login dan password akun Anda.</p>
            </div>
        </div>
        <div class="list-group list-group-flush">
            <a href="{{ route('profile.edit') }}" class="list-group-item list-group-item-action px-3 px-md-4 py-3">
                <div class="d-flex align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <span class="avtar avtar-s bg-light-primary text-primary"><i class="ti ti-lock" aria-hidden="true"></i></span>
                        <div>
                            <strong class="d-block">Email dan Password</strong>
                            <span class="text-muted small">Perbarui informasi login dan keamanan akun.</span>
                        </div>
                    </div>
                    <i class="ti ti-chevron-right text-muted" aria-hidden="true"></i>
                </div>
            </a>
        </div>
    </section>

    @unless ($employee->isVerified())
        <section class="content-section" aria-labelledby="review-heading">
            <div class="content-section-header"><div><h2 id="review-heading">Review dan Pengajuan</h2><p>Periksa dokumen sebelum mengirim profil ke HR.</p></div><a href="{{ route('pegawai.profile.wizard.show', 'review') }}" class="btn btn-primary">Buka Review</a></div>
        </section>
    @endunless
    </div>
@endsection
