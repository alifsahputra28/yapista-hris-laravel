@php
    $user = Auth::user();
    $dashboardRoute = match ($user?->role) {
        'panitia' => 'scanner.dashboard',
        'pegawai' => 'pegawai.dashboard',
        default => 'dashboard',
    };
    $isAdmin = $user?->isSuperAdmin() || $user?->isHrAdmin();
    $isPegawai = $user?->isPegawai();
@endphp

<nav class="pc-sidebar">
    <div class="navbar-wrapper">
        <div class="m-header">
            <a href="{{ route($dashboardRoute) }}" class="b-brand text-primary">
                <img src="{{ asset('assets/images/logo-dark.svg') }}" class="img-fluid logo-lg" alt="logo">
            </a>
        </div>

        <div class="navbar-content">
            <ul class="pc-navbar">
                <li class="pc-item {{ request()->routeIs($dashboardRoute) ? 'active' : '' }}">
                    <a href="{{ route($dashboardRoute) }}" class="pc-link">
                        <span class="pc-micon"><i class="ti ti-dashboard"></i></span>
                        <span class="pc-mtext">Dashboard</span>
                    </a>
                </li>

                @if ($isAdmin)
                    <li class="pc-item pc-caption">
                        <label>Master Data</label>
                        <i class="ti ti-database"></i>
                    </li>

                    <li class="pc-item {{ request()->routeIs('institutions.*') ? 'active' : '' }}">
                        <a href="{{ route('institutions.index') }}" class="pc-link">
                            <span class="pc-micon"><i class="ti ti-building"></i></span>
                            <span class="pc-mtext">Unit Kerja</span>
                        </a>
                    </li>

                    <li class="pc-item {{ request()->routeIs('positions.*') ? 'active' : '' }}">
                        <a href="{{ route('positions.index') }}" class="pc-link">
                            <span class="pc-micon"><i class="ti ti-briefcase"></i></span>
                            <span class="pc-mtext">Jabatan</span>
                        </a>
                    </li>

                    <li class="pc-item pc-caption">
                        <label>Pegawai</label>
                        <i class="ti ti-users"></i>
                    </li>

                    <li class="pc-item {{ request()->routeIs('employees.*') ? 'active' : '' }}">
                        <a href="{{ route('employees.index') }}" class="pc-link">
                            <span class="pc-micon"><i class="ti ti-users"></i></span>
                            <span class="pc-mtext">Data Pegawai</span>
                        </a>
                    </li>

                    <li class="pc-item pc-caption">
                        <label>Registrasi</label>
                        <i class="ti ti-mail"></i>
                    </li>

                    <li class="pc-item {{ request()->routeIs('invitations.*') ? 'active' : '' }}">
                        <a href="{{ route('invitations.index') }}" class="pc-link">
                            <span class="pc-micon"><i class="ti ti-mail"></i></span>
                            <span class="pc-mtext">Undangan Pegawai</span>
                        </a>
                    </li>
                @endif

                @if ($isPegawai)
                    <li class="pc-item {{ request()->routeIs('pegawai.profile.*') ? 'active' : '' }}">
                        <a href="{{ route('pegawai.profile.show') }}" class="pc-link">
                            <span class="pc-micon"><i class="ti ti-user"></i></span>
                            <span class="pc-mtext">Profil Saya</span>
                        </a>
                    </li>

                    <li class="pc-item {{ request()->routeIs('pegawai.documents.*') ? 'active' : '' }}">
                        <a href="{{ route('pegawai.documents.index') }}" class="pc-link">
                            <span class="pc-micon"><i class="ti ti-files"></i></span>
                            <span class="pc-mtext">Dokumen Saya</span>
                        </a>
                    </li>
                @endif
            </ul>
        </div>
    </div>
</nav>
