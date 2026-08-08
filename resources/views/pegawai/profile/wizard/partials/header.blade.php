@php
    $verificationBadges = [
        'draft' => ['Draft', 'bg-light-secondary text-secondary'],
        'submitted' => ['Menunggu Verifikasi', 'bg-light-warning text-warning'],
        'verified' => ['Terverifikasi', 'bg-light-success text-success'],
        'rejected' => ['Perlu Perbaikan', 'bg-light-danger text-danger'],
    ];
    [$verificationLabel, $verificationClass] = $verificationBadges[$employee->verification_status] ?? [ucfirst((string) $employee->verification_status), 'bg-light-secondary text-secondary'];
@endphp
<x-page-header
    :title="$steps[$step]['label']"
    :subtitle="$employee->isVerified() ? 'Tinjau data profil tambahan Anda.' : 'Lengkapi data pada bagian ini.'"
    :badge-label="$verificationLabel"
    :badge-class="$verificationClass"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('pegawai.dashboard')],
        ['label' => 'Profil Saya', 'url' => route('pegawai.profile.show')],
        ['label' => $steps[$step]['short_label']],
    ]"
>
    <x-slot:meta><x-employee-context :employee="$employee" /></x-slot:meta>
    <x-slot:actions>
        <a href="{{ route('pegawai.profile.show') }}" class="btn btn-light-secondary"><i class="ti ti-arrow-left"></i> Kembali</a>
    </x-slot:actions>
</x-page-header>

<div class="profile-progress">
    <div class="d-flex justify-content-between gap-3 mb-2">
        <span class="fw-semibold">{{ $employee->isVerified() ? 'Data profil tambahan' : 'Kelengkapan data profil' }}</span>
        <strong>{{ $profileProgress['percentage'] }}% terisi</strong>
    </div>
    <div class="progress" style="height: 8px;" role="progressbar" aria-label="Kelengkapan profil" aria-valuenow="{{ $profileProgress['percentage'] }}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" style="width: {{ $profileProgress['percentage'] }}%"></div></div>
    @if ($employee->isVerified())
        <small class="text-muted">Data tambahan bersifat opsional dan tidak memengaruhi status pegawai.</small>
    @endif
</div>
