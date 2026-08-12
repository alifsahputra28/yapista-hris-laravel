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
        ['label' => 'Beranda', 'url' => route('pegawai.dashboard')],
        ['label' => 'Akun', 'url' => route('pegawai.profile.show')],
        ['label' => $steps[$step]['short_label']],
    ]"
>
    <x-slot:actions>
        <a href="{{ route('pegawai.profile.show') }}" class="btn btn-light-secondary"><i class="ti ti-arrow-left"></i> Kembali</a>
    </x-slot:actions>
</x-page-header>

@if ($step !== 'review')
    @if ($employee->isVerified())
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 border-bottom pb-3 mb-4">
            <div>
                <span class="fw-semibold">Data tambahan</span>
                <span class="text-muted small ms-1">Opsional</span>
            </div>
            <span class="text-muted small">{{ $profileProgress['percentage'] }}% terisi</span>
        </div>
    @else
        <div class="profile-progress">
            <div class="d-flex justify-content-between gap-3 mb-2">
                <span class="fw-semibold">Kelengkapan data profil</span>
                <strong>{{ $profileProgress['percentage'] }}% terisi</strong>
            </div>
            <div class="progress" role="progressbar" aria-label="Kelengkapan profil" aria-valuenow="{{ $profileProgress['percentage'] }}" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar" style="width: {{ $profileProgress['percentage'] }}%"></div>
            </div>
        </div>
    @endif
@endif
