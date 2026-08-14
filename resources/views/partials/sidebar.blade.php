@php
    $user = Auth::user();
    $dashboardRoute = match ($user?->role) {
        'panitia' => 'scanner.dashboard',
        'pegawai' => 'pegawai.dashboard',
        default => 'dashboard',
    };
    $isAdmin = $user?->isSuperAdmin() || $user?->isHrAdmin();
    $isPanitia = $user?->isPanitia();
    $isPegawai = $user?->isPegawai();
@endphp

<nav class="pc-sidebar">
    <div class="navbar-wrapper">
        <div class="m-header">
            <a href="{{ route($dashboardRoute) }}" class="b-brand text-primary">
                <x-application-logo class="sidebar-brand-logo" image-class="img-fluid" />
            </a>
        </div>

        <div class="navbar-content">
            <ul class="pc-navbar">
                <li class="pc-item {{ request()->routeIs($dashboardRoute) ? 'active' : '' }}">
                    <a href="{{ route($dashboardRoute) }}" class="pc-link">
                        <span class="pc-micon"><i class="ti ti-dashboard"></i></span>
                        <span class="pc-mtext">{{ $isPegawai ? 'Beranda' : 'Dashboard' }}</span>
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

                    <li class="pc-item {{ request()->routeIs('verifications.*') ? 'active' : '' }}">
                        <a href="{{ route('verifications.index') }}" class="pc-link">
                            <span class="pc-micon"><i class="ti ti-user-check"></i></span>
                            <span class="pc-mtext">Verifikasi Pegawai</span>
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

                    <li class="pc-item pc-caption">
                        <label>Kegiatan</label>
                        <i class="ti ti-calendar-event"></i>
                    </li>

                    <li class="pc-item {{ request()->routeIs('events.*') || request()->routeIs('event-participants.*') ? 'active' : '' }}">
                        <a href="{{ route('events.index') }}" class="pc-link">
                            <span class="pc-micon"><i class="ti ti-calendar-event"></i></span>
                            <span class="pc-mtext">Data Kegiatan</span>
                        </a>
                    </li>

                    <li class="pc-item pc-caption">
                        <label>Laporan</label>
                        <i class="ti ti-file-report"></i>
                    </li>

                    <li class="pc-item {{ request()->routeIs('reports.employees') ? 'active' : '' }}">
                        <a href="{{ route('reports.employees') }}" class="pc-link">
                            <span class="pc-micon"><i class="ti ti-users"></i></span>
                            <span class="pc-mtext">Laporan Pegawai</span>
                        </a>
                    </li>

                    <li class="pc-item {{ request()->routeIs('reports.events') || request()->routeIs('reports.events.attendances') ? 'active' : '' }}">
                        <a href="{{ route('reports.events') }}" class="pc-link">
                            <span class="pc-micon"><i class="ti ti-calendar-event"></i></span>
                            <span class="pc-mtext">Laporan Kegiatan</span>
                        </a>
                    </li>

                @endif

                @if ($isPanitia)
                    <li class="pc-item pc-caption">
                        <label>Scanner</label>
                        <i class="ti ti-qrcode"></i>
                    </li>

                    <li class="pc-item {{ request()->routeIs('scanner.dashboard') || request()->routeIs('events.scanner') || request()->routeIs('events.attendances.*') ? 'active' : '' }}">
                        <a href="{{ route('scanner.dashboard') }}" class="pc-link">
                            <span class="pc-micon"><i class="ti ti-calendar-event"></i></span>
                            <span class="pc-mtext">Kegiatan Aktif</span>
                        </a>
                    </li>
                @endif

                @if ($isPegawai)
                    <li class="pc-item pc-caption">
                        <label>Layanan Pegawai</label>
                        <i class="ti ti-user"></i>
                    </li>

                    <li class="pc-item {{ request()->routeIs('pegawai.activities.*') ? 'active' : '' }}">
                        <a href="{{ route('pegawai.activities.index') }}" class="pc-link">
                            <span class="pc-micon"><i class="ti ti-calendar-event"></i></span>
                            <span class="pc-mtext">Kegiatan</span>
                        </a>
                    </li>

                    @if (Route::has('pegawai.id-card.show'))
                        <li class="pc-item {{ request()->routeIs('pegawai.id-card.*') ? 'active' : '' }}">
                            <a href="{{ route('pegawai.id-card.show') }}" class="pc-link">
                                <span class="pc-micon"><i class="ti ti-id"></i></span>
                                <span class="pc-mtext">ID Card</span>
                            </a>
                        </li>
                    @endif

                    <li class="pc-item {{ request()->routeIs('pegawai.documents.*') ? 'active' : '' }}">
                        <a href="{{ route('pegawai.documents.index') }}" class="pc-link">
                            <span class="pc-micon"><i class="ti ti-files"></i></span>
                            <span class="pc-mtext">Dokumen</span>
                        </a>
                    </li>

                    <li class="pc-item {{ request()->routeIs('pegawai.profile.*') || request()->routeIs('profile.edit') ? 'active' : '' }}">
                        <a href="{{ route('pegawai.profile.show') }}" class="pc-link">
                            <span class="pc-micon"><i class="ti ti-user"></i></span>
                            <span class="pc-mtext">Akun</span>
                        </a>
                    </li>
                @endif
            </ul>
        </div>
    </div>
</nav>
