<div class="page-header"><div class="page-block"><div class="row align-items-center"><div class="col-md-12"><ul class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('pegawai.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('pegawai.profile.show') }}">Profil Saya</a></li>
    <li class="breadcrumb-item" aria-current="page">{{ $steps[$step]['label'] }}</li>
</ul></div></div></div></div>

@php
    $verificationBadges = [
        'draft' => ['Draft', 'bg-light-secondary text-secondary'],
        'submitted' => ['Menunggu Verifikasi', 'bg-light-warning text-warning'],
        'verified' => ['Terverifikasi', 'bg-light-success text-success'],
        'rejected' => ['Perlu Perbaikan', 'bg-light-danger text-danger'],
    ];
    [$verificationLabel, $verificationClass] = $verificationBadges[$employee->verification_status] ?? [ucfirst((string) $employee->verification_status), 'bg-light-secondary text-secondary'];
@endphp
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
    <div><div class="d-flex align-items-center flex-wrap gap-2 mb-1"><h3 class="mb-0">Profil Saya</h3><span class="badge {{ $verificationClass }}">{{ $verificationLabel }}</span></div><p class="mb-0 text-muted">Lengkapi data administrasi kepegawaian secara bertahap.</p></div>
    <a href="{{ route('pegawai.profile.show') }}" class="btn btn-light-secondary"><i class="ti ti-arrow-left"></i> Kembali ke Profil</a>
</div>

<div class="mb-4">
    <div class="d-flex justify-content-between gap-3 mb-2"><span class="fw-semibold">Kelengkapan data profil</span><strong>{{ $profileProgress['percentage'] }}%</strong></div>
    <div class="progress" style="height: 8px;" role="progressbar" aria-label="Kelengkapan profil" aria-valuenow="{{ $profileProgress['percentage'] }}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" style="width: {{ $profileProgress['percentage'] }}%"></div></div>
    <small class="text-muted">Kelengkapan data profil - belum termasuk dokumen.</small>
</div>
