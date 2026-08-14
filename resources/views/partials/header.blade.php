@php
    $headerUser = Auth::user();
    $headerEmployee = $headerUser?->isPegawai() ? $headerUser->employee : null;
    $headerAvatar = $headerEmployee?->photo
        ? route('employees.photo', $headerEmployee)
        : asset('assets/images/user/avatar-2.jpg');
    $roleLabels = [
        'super_admin' => 'Super Admin',
        'hr_admin' => 'HR Admin',
        'panitia' => 'Panitia',
        'pegawai' => 'Pegawai',
    ];
@endphp

@if ($headerUser?->isPegawai())
    <header class="employee-mobile-appbar d-lg-none">
        <a href="{{ route('pegawai.dashboard') }}" class="employee-mobile-brand" aria-label="Beranda YAPISTA HRIS">
            <x-application-logo class="employee-mobile-logo" image-class="img-fluid" />
        </a>
        <a href="{{ route('pegawai.profile.show') }}" class="employee-mobile-account" aria-label="Buka Akun">
            <img src="{{ $headerAvatar }}" alt="Foto {{ $headerUser->name }}" class="user-avtar">
        </a>
    </header>
@endif

<header class="pc-header {{ $headerUser?->isPegawai() ? 'employee-desktop-header' : '' }}">
    <div class="header-wrapper">
        <div class="me-auto pc-mob-drp">
            <ul class="list-unstyled mb-0">
                <li class="pc-h-item pc-sidebar-collapse">
                    <a href="#" class="pc-head-link ms-0" id="sidebar-hide" aria-label="Tutup sidebar" title="Tutup sidebar">
                        <i class="ti ti-menu-2" aria-hidden="true"></i>
                    </a>
                </li>
                <li class="pc-h-item pc-sidebar-popup">
                    <a href="#" class="pc-head-link ms-0" id="mobile-collapse" aria-label="Buka menu" title="Buka menu">
                        <i class="ti ti-menu-2" aria-hidden="true"></i>
                    </a>
                </li>
            </ul>
        </div>

        <div class="ms-auto">
            <ul class="list-unstyled mb-0">
                <li class="dropdown pc-h-item header-user-profile">
                    <a class="pc-head-link dropdown-toggle arrow-none me-0"
                       data-bs-toggle="dropdown"
                       href="#"
                       role="button"
                       aria-haspopup="true"
                       data-bs-auto-close="outside"
                       aria-expanded="false">
                        <img src="{{ $headerAvatar }}" alt="Foto {{ $headerUser?->name }}" class="user-avtar">
                        <span class="d-none d-sm-inline">{{ $headerUser?->name ?? 'Pengguna' }}</span>
                    </a>

                    <div class="dropdown-menu dropdown-user-profile dropdown-menu-end pc-h-dropdown">
                        <div class="dropdown-header">
                            <h6 class="mb-1">{{ $headerUser?->name ?? 'Pengguna' }}</h6>
                            <span class="text-muted">{{ $roleLabels[$headerUser?->role] ?? ucfirst((string) $headerUser?->role) }}</span>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a href="{{ $headerUser?->isPegawai() ? route('pegawai.profile.show') : route('profile.edit') }}" class="dropdown-item">
                            <i class="ti ti-user" aria-hidden="true"></i>
                            <span>{{ $headerUser?->isPegawai() ? 'Akun' : 'Profil Saya' }}</span>
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item">
                                <i class="ti ti-power" aria-hidden="true"></i>
                                <span>{{ $headerUser?->isPegawai() ? 'Keluar' : 'Logout' }}</span>
                            </button>
                        </form>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</header>
