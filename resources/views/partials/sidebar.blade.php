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
    $organizationActive = request()->routeIs('institutions.*', 'positions.*', 'options.*');
    $employeeManagementActive = request()->routeIs('employees.*', 'verifications.*', 'invitations.*', 'employee-documents.*');
    $activityActive = request()->routeIs('events.*', 'event-participants.*', 'event-attendances.*');
    $reportsActive = request()->routeIs('reports.*');
    $userManagementActive = request()->routeIs('users.*');
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
                        <label>Modul HRIS</label>
                        <i class="ti ti-layout-grid"></i>
                    </li>

                    <li class="pc-item pc-hasmenu {{ $organizationActive ? 'active pc-trigger' : '' }}" data-nav-group="organization">
                        <a href="#!" class="pc-link" aria-expanded="{{ $organizationActive ? 'true' : 'false' }}">
                            <span class="pc-micon"><i class="ti ti-building-community"></i></span>
                            <span class="pc-mtext">Organisasi</span>
                            <span class="pc-arrow"><i class="ti ti-chevron-right"></i></span>
                        </a>
                        <ul class="pc-submenu">
                            <li class="pc-item {{ request()->routeIs('institutions.*') ? 'active' : '' }}"><a class="pc-link" href="{{ route('institutions.index') }}">Unit Kerja</a></li>
                            <li class="pc-item {{ request()->routeIs('positions.*') ? 'active' : '' }}"><a class="pc-link" href="{{ route('positions.index') }}">Jabatan</a></li>
                            @if ($user?->isSuperAdmin())
                                <li class="pc-item {{ request()->routeIs('options.*') ? 'active' : '' }}"><a class="pc-link" href="{{ route('options.index') }}">Options</a></li>
                            @endif
                        </ul>
                    </li>

                    <li class="pc-item pc-hasmenu {{ $employeeManagementActive ? 'active pc-trigger' : '' }}" data-nav-group="employees">
                        <a href="#!" class="pc-link" aria-expanded="{{ $employeeManagementActive ? 'true' : 'false' }}">
                            <span class="pc-micon"><i class="ti ti-users"></i></span>
                            <span class="pc-mtext">Manajemen Pegawai</span>
                            <span class="pc-arrow"><i class="ti ti-chevron-right"></i></span>
                        </a>
                        <ul class="pc-submenu">
                            <li class="pc-item {{ request()->routeIs('employees.*') ? 'active' : '' }}"><a class="pc-link" href="{{ route('employees.index') }}">Data Pegawai</a></li>
                            <li class="pc-item {{ request()->routeIs('verifications.*') ? 'active' : '' }}"><a class="pc-link" href="{{ route('verifications.index') }}">Verifikasi Pegawai</a></li>
                            <li class="pc-item {{ request()->routeIs('invitations.*') ? 'active' : '' }}"><a class="pc-link" href="{{ route('invitations.index') }}">Undangan Pegawai</a></li>
                        </ul>
                    </li>

                    @if ($user?->isSuperAdmin())
                        <li class="pc-item {{ $userManagementActive ? 'active' : '' }}">
                            <a href="{{ route('users.index') }}" class="pc-link">
                                <span class="pc-micon"><i class="ti ti-user-check" aria-hidden="true"></i></span>
                                <span class="pc-mtext">Manajemen User</span>
                            </a>
                        </li>
                    @endif

                    <li class="pc-item pc-hasmenu {{ $activityActive ? 'active pc-trigger' : '' }}" data-nav-group="activities">
                        <a href="#!" class="pc-link" aria-expanded="{{ $activityActive ? 'true' : 'false' }}">
                            <span class="pc-micon"><i class="ti ti-calendar-event"></i></span>
                            <span class="pc-mtext">Kegiatan &amp; Kehadiran</span>
                            <span class="pc-arrow"><i class="ti ti-chevron-right"></i></span>
                        </a>
                        <ul class="pc-submenu">
                            <li class="pc-item {{ $activityActive ? 'active' : '' }}"><a class="pc-link" href="{{ route('events.index') }}">Data Kegiatan</a></li>
                        </ul>
                    </li>

                    <li class="pc-item pc-hasmenu {{ $reportsActive ? 'active pc-trigger' : '' }}" data-nav-group="reports">
                        <a href="#!" class="pc-link" aria-expanded="{{ $reportsActive ? 'true' : 'false' }}">
                            <span class="pc-micon"><i class="ti ti-file-report"></i></span>
                            <span class="pc-mtext">Laporan</span>
                            <span class="pc-arrow"><i class="ti ti-chevron-right"></i></span>
                        </a>
                        <ul class="pc-submenu">
                            <li class="pc-item {{ request()->routeIs('reports.employees', 'reports.employees.export') ? 'active' : '' }}"><a class="pc-link" href="{{ route('reports.employees') }}">Laporan Pegawai</a></li>
                            <li class="pc-item {{ request()->routeIs('reports.events', 'reports.events.export', 'reports.events.attendances', 'reports.events.attendances.export') ? 'active' : '' }}"><a class="pc-link" href="{{ route('reports.events') }}">Laporan Kegiatan</a></li>
                        </ul>
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
