@php
    $employeeNavigation = [
        ['label' => 'Beranda', 'icon' => 'ti-home', 'route' => 'pegawai.dashboard', 'active' => request()->routeIs('pegawai.dashboard')],
        ['label' => 'Kegiatan', 'icon' => 'ti-calendar-event', 'route' => 'pegawai.activities.index', 'active' => request()->routeIs('pegawai.activities.*')],
        ['label' => 'ID Card', 'icon' => 'ti-id', 'route' => 'pegawai.id-card.show', 'active' => request()->routeIs('pegawai.id-card.*')],
        ['label' => 'Dokumen', 'icon' => 'ti-files', 'route' => 'pegawai.documents.index', 'active' => request()->routeIs('pegawai.documents.*')],
        ['label' => 'Akun', 'icon' => 'ti-user-circle', 'route' => 'pegawai.profile.show', 'active' => request()->routeIs('pegawai.profile.*') || request()->routeIs('profile.edit')],
    ];
@endphp

<nav class="employee-bottom-nav d-lg-none" aria-label="Navigasi utama pegawai">
    @foreach ($employeeNavigation as $item)
        <a href="{{ route($item['route']) }}" class="employee-bottom-nav-item {{ $item['active'] ? 'active' : '' }}" @if ($item['active']) aria-current="page" @endif>
            <i class="ti {{ $item['icon'] }}" aria-hidden="true"></i>
            <span>{{ $item['label'] }}</span>
        </a>
    @endforeach
</nav>
