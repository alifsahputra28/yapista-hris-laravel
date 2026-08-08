@php
    $display = fn ($value) => filled($value) ? $value : 'Belum diisi';
    $mask = fn ($value) => filled($value) ? str_repeat('*', max(strlen($value) - 4, 0)).substr($value, -4) : 'Belum diisi';
    $detail = $employee->administrativeDetail;
    $highestEducation = $employee->educations->firstWhere('is_highest', true);
    $taxLabels = \App\Models\EmployeeAdministrativeDetail::TAX_STATUSES;
    $bpjsLabels = \App\Models\EmployeeAdministrativeDetail::BPJS_STATUSES;
    $employeeTypes = ['guru' => 'Guru', 'dosen' => 'Dosen', 'tenaga_kependidikan' => 'Tenaga Kependidikan', 'staff_yayasan' => 'Staff Yayasan', 'security' => 'Security', 'cleaning_service' => 'Cleaning Service', 'driver' => 'Driver', 'teknisi' => 'Teknisi'];
@endphp

@if ($employee->isProfileSubmitted())
    <div class="alert alert-warning">
        <i class="ti ti-clock me-1"></i>
        <strong>Profil sedang menunggu pemeriksaan HR/Admin.</strong>
        @if ($employee->profile_submitted_at)<span class="d-block mt-1">Dikirim pada {{ $employee->profile_submitted_at->format('d M Y H:i') }}.</span>@endif
    </div>
@else
    <div class="alert alert-light-primary"><i class="ti ti-info-circle me-1"></i> Lengkapi data dan dokumen berikut sebelum mengirim profil untuk diperiksa.</div>
@endif

<div class="card">
    <div class="card-header"><h5 class="mb-0">Kesiapan Pengajuan</h5></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4"><small class="text-muted d-block">Data Profil</small><strong>{{ $submissionChecklist['data_progress']['percentage'] }}%</strong></div>
            <div class="col-md-4"><small class="text-muted d-block">Bagian Lengkap</small><strong>{{ $submissionChecklist['data_progress']['completed_sections'] }}/{{ $submissionChecklist['data_progress']['total_sections'] }}</strong></div>
            <div class="col-md-4"><small class="text-muted d-block">Dokumen Wajib</small><strong>{{ $submissionChecklist['completed_required_documents'] }}/{{ $submissionChecklist['required_documents'] }}</strong></div>
        </div>
        <div class="progress mt-3" style="height: 8px" role="progressbar" aria-label="Kelengkapan data profil" aria-valuenow="{{ $submissionChecklist['data_progress']['percentage'] }}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" style="width: {{ $submissionChecklist['data_progress']['percentage'] }}%"></div></div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-1">Dokumen Utama</h5><p class="mb-0 text-muted">Dokumen disimpan secara private dan hanya dapat diakses melalui aplikasi.</p></div>
    <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Dokumen</th><th>Ketentuan</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
        @foreach ($submissionChecklist['main_documents'] as $documentType => $item)
            @include('pegawai.profile.wizard.partials.document-row', ['item' => $item, 'documentType' => $documentType])
        @endforeach
    </tbody></table></div></div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2"><div><h5 class="mb-1">Dokumen Pendidikan</h5><p class="mb-0 text-muted">Ijazah pendidikan tertinggi wajib. Transkrip bersifat opsional.</p></div>@if ($editable)<a href="{{ route('pegawai.profile.educations.create') }}" class="btn btn-sm btn-primary"><i class="ti ti-plus"></i> Tambah Pendidikan</a>@endif</div>
    <div class="card-body p-0">
        @forelse ($submissionChecklist['education_documents'] as $educationDocument)
            <div class="px-3 pt-3"><strong>{{ $educationDocument['education_label'] }}</strong>@if ($educationDocument['is_highest']) <span class="badge bg-light-success text-success ms-1">Pendidikan Tertinggi</span>@endif</div>
            <div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Dokumen</th><th>Ketentuan</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
                @include('pegawai.profile.wizard.partials.document-row', ['item' => $educationDocument['ijazah'], 'documentType' => 'ijazah', 'educationId' => $educationDocument['education_id']])
                @include('pegawai.profile.wizard.partials.document-row', ['item' => $educationDocument['transkrip'], 'documentType' => 'transkrip', 'educationId' => $educationDocument['education_id']])
            </tbody></table></div>
        @empty
            <div class="empty-state"><i class="ti ti-school fs-1 text-muted"></i><h6 class="mt-3 mb-1">Belum ada riwayat pendidikan</h6><p class="text-muted mb-0">Tambahkan pendidikan sebelum mengunggah ijazah.</p></div>
        @endforelse
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2"><div><h5 class="mb-1">Dokumen Sertifikasi</h5><p class="mb-0 text-muted">File sertifikat bersifat opsional.</p></div>@if ($editable)<a href="{{ route('pegawai.profile.certifications.create') }}" class="btn btn-sm btn-primary"><i class="ti ti-plus"></i> Tambah Sertifikasi</a>@endif</div>
    <div class="card-body p-0">
        @forelse ($submissionChecklist['certification_documents'] as $certificationDocument)
            <div class="px-3 pt-3"><strong>{{ $certificationDocument['certification_label'] }}</strong>@if ($certificationDocument['issuer'])<small class="text-muted d-block">{{ $certificationDocument['issuer'] }}</small>@endif</div>
            <div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Dokumen</th><th>Ketentuan</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
                @include('pegawai.profile.wizard.partials.document-row', ['item' => $certificationDocument['document'], 'documentType' => 'sertifikat', 'certificationId' => $certificationDocument['certification_id']])
            </tbody></table></div>
        @empty
            <div class="empty-state"><i class="ti ti-certificate fs-1 text-muted"></i><h6 class="mt-3 mb-1">Belum ada sertifikasi</h6><p class="text-muted mb-0">Sertifikasi tidak diwajibkan untuk pengajuan profil.</p></div>
        @endforelse
    </div>
</div>

@foreach ($submissionChecklist['warnings'] as $warning)<div class="alert alert-warning"><i class="ti ti-alert-triangle me-1"></i>{{ $warning }}</div>@endforeach

<div class="card"><div class="card-header"><h5 class="mb-0">Review Data</h5></div><div class="card-body">
    <div class="row g-3">
        <div class="col-md-6"><small class="text-muted d-block">Nama Lengkap</small>{{ $employee->full_name }}</div>
        <div class="col-md-6"><small class="text-muted d-block">NUP / Nomor Pegawai</small>{{ $employee->formatted_employee_number }}</div>
        <div class="col-md-6"><small class="text-muted d-block">NIK</small>{{ $employee->masked_nik ?? 'Belum diisi' }}</div>
        <div class="col-md-6"><small class="text-muted d-block">Nomor Kartu Keluarga</small>{{ $mask($employee->family_card_number) }}</div>
        <div class="col-md-6"><small class="text-muted d-block">Unit & Jabatan</small>{{ $employee->institution?->name ?? 'Belum diisi' }} / {{ $employee->position?->name ?? 'Belum diisi' }}</div>
        <div class="col-md-6"><small class="text-muted d-block">Jenis & Status Pegawai</small>{{ $employeeTypes[$employee->employee_type] ?? $employee->employee_type }} / {{ $employee->employment_status }}</div>
        <div class="col-md-6"><small class="text-muted d-block">Kontak</small>{{ $display($employee->phone) }} / {{ $display($employee->whatsapp_number) }}</div>
        <div class="col-md-6"><small class="text-muted d-block">Email Pribadi</small>{{ $display($employee->email) }}</div>
        <div class="col-12"><small class="text-muted d-block">Alamat KTP / Domisili</small>{{ $display($employee->identity_address) }} / {{ $display($employee->address) }}</div>
        <div class="col-md-6"><small class="text-muted d-block">Kontak Darurat</small>{{ $display($employee->emergency_contact_name) }} / {{ $display($employee->emergency_contact_phone) }}</div>
        <div class="col-md-6"><small class="text-muted d-block">Anggota Keluarga</small>{{ $employee->familyMembers->count() }} data, seluruh NIK ditampilkan tersamarkan.</div>
        <div class="col-md-6"><small class="text-muted d-block">Pendidikan Tertinggi</small>{{ $highestEducation ? $highestEducation->education_level_label.' - '.$highestEducation->institution_name.' ('.$highestEducation->masked_certificate_number.')' : 'Belum diisi' }}</div>
        <div class="col-md-6"><small class="text-muted d-block">Sertifikasi</small>{{ $employee->certifications->count() }} data, nomor sertifikat tersamarkan.</div>
        <div class="col-md-6"><small class="text-muted d-block">Rekening</small>{{ $display($detail?->bank_name) }} / {{ $display($detail?->masked_bank_account_number) }}</div>
        <div class="col-md-6"><small class="text-muted d-block">Pajak</small>{{ $detail?->tax_status ? ($taxLabels[$detail->tax_status] ?? $detail->tax_status) : 'Belum diisi' }} / {{ $display($detail?->masked_tax_identification_number) }}</div>
        <div class="col-md-6"><small class="text-muted d-block">BPJS Kesehatan</small>{{ $detail?->bpjs_health_status ? ($bpjsLabels[$detail->bpjs_health_status] ?? $detail->bpjs_health_status) : 'Belum diisi' }} / {{ $display($detail?->masked_bpjs_health_number) }}</div>
        <div class="col-md-6"><small class="text-muted d-block">BPJS Ketenagakerjaan</small>{{ $detail?->bpjs_employment_status ? ($bpjsLabels[$detail->bpjs_employment_status] ?? $detail->bpjs_employment_status) : 'Belum diisi' }} / {{ $display($detail?->masked_bpjs_employment_number) }}</div>
    </div>
</div></div>

<div class="card"><div class="card-header"><h5 class="mb-0">Checklist Final</h5></div><div class="card-body">
    @if ($submissionChecklist['missing_data'] === [] && $submissionChecklist['missing_documents'] === [])
        <div class="alert alert-success"><i class="ti ti-circle-check me-1"></i>Data profil dan dokumen wajib telah lengkap.</div>
    @else
        <div class="alert alert-warning"><i class="ti ti-alert-circle me-1"></i>Profil belum dapat dikirim. Lengkapi data dan dokumen yang masih kurang.</div>
        @if ($submissionChecklist['missing_data'] !== [])<h6>Data belum lengkap</h6><ul>@foreach ($submissionChecklist['missing_data'] as $missing)<li>{{ $missing }}</li>@endforeach</ul>@endif
        @if ($submissionChecklist['missing_documents'] !== [])<h6>Dokumen belum lengkap</h6><ul>@foreach ($submissionChecklist['missing_documents'] as $missing)<li>{{ $missing }}</li>@endforeach</ul>@endif
    @endif

    @if ($employee->isProfileSubmitted())
        <p class="text-muted mb-0">Profil telah dikirim dan seluruh perubahan dikunci sampai proses pemeriksaan selesai.</p>
    @elseif ($editable && $submissionChecklist['can_submit'])
        <form method="POST" action="{{ route('pegawai.profile.submit') }}" data-wizard-form onsubmit="return confirm('Setelah dikirim, profil tidak dapat diubah sampai diperiksa oleh HR/Admin. Lanjutkan?')">
            @csrf
            <div class="form-check mb-3"><input id="declaration" name="declaration" value="1" type="checkbox" class="form-check-input @error('declaration') is-invalid @enderror" required><label for="declaration" class="form-check-label">Saya memastikan data dan dokumen yang diberikan benar serta sesuai dengan dokumen resmi.</label>@error('declaration')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <button type="submit" class="btn btn-success"><i class="ti ti-send"></i> Kirim untuk Verifikasi</button>
        </form>
    @elseif ($editable)
        <button type="button" class="btn btn-success" disabled><i class="ti ti-send"></i> Kirim untuk Verifikasi</button>
    @endif
</div></div>

<div class="d-flex flex-wrap justify-content-between gap-2"><a href="{{ route('pegawai.profile.wizard.show', 'administration') }}" class="btn btn-light-secondary"><i class="ti ti-arrow-left"></i> Kembali</a><a href="{{ route('pegawai.profile.show') }}" class="btn btn-primary">Kembali ke Profil</a></div>
