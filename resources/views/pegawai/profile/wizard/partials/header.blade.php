<div class="page-header"><div class="page-block"><div class="row align-items-center"><div class="col-md-12"><ul class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('pegawai.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('pegawai.profile.show') }}">Profil Saya</a></li>
    <li class="breadcrumb-item" aria-current="page">{{ $steps[$step]['label'] }}</li>
</ul></div></div></div></div>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
    <div><h3 class="mb-1">Profil Saya</h3><p class="mb-0 text-muted">Lengkapi data administrasi kepegawaian secara bertahap.</p></div>
    <a href="{{ route('pegawai.profile.show') }}" class="btn btn-light-secondary"><i class="ti ti-arrow-left"></i> Kembali ke Profil</a>
</div>

<div class="mb-4">
    <div class="d-flex justify-content-between gap-3 mb-2"><span class="fw-semibold">Kelengkapan data profil</span><strong>{{ $profileProgress['percentage'] }}%</strong></div>
    <div class="progress" style="height: 8px;" role="progressbar" aria-label="Kelengkapan profil" aria-valuenow="{{ $profileProgress['percentage'] }}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" style="width: {{ $profileProgress['percentage'] }}%"></div></div>
    <small class="text-muted">Kelengkapan data profil - belum termasuk dokumen.</small>
</div>
