@php
    $dashboardRoute = match (Auth::user()?->role) {
        'panitia' => 'scanner.dashboard',
        'pegawai' => 'pegawai.dashboard',
        default => 'dashboard',
    };
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
                <li class="pc-item">
                    <a href="{{ route($dashboardRoute) }}" class="pc-link">
                        <span class="pc-micon"><i class="ti ti-dashboard"></i></span>
                        <span class="pc-mtext">Dashboard</span>
                    </a>
                </li>

                <li class="pc-item pc-caption">
                    <label>HRIS YAPISTA</label>
                    <i class="ti ti-users"></i>
                </li>

                <li class="pc-item">
                    <a href="#!" class="pc-link">
                        <span class="pc-micon"><i class="ti ti-user-plus"></i></span>
                        <span class="pc-mtext">Registrasi Pegawai</span>
                    </a>
                </li>

                <li class="pc-item">
                    <a href="#!" class="pc-link">
                        <span class="pc-micon"><i class="ti ti-users"></i></span>
                        <span class="pc-mtext">Data Pegawai</span>
                    </a>
                </li>

                <li class="pc-item">
                    <a href="#!" class="pc-link">
                        <span class="pc-micon"><i class="ti ti-id"></i></span>
                        <span class="pc-mtext">ID Card</span>
                    </a>
                </li>

                <li class="pc-item">
                    <a href="#!" class="pc-link">
                        <span class="pc-micon"><i class="ti ti-calendar-event"></i></span>
                        <span class="pc-mtext">Kegiatan</span>
                    </a>
                </li>

                <li class="pc-item">
                    <a href="#!" class="pc-link">
                        <span class="pc-micon"><i class="ti ti-qrcode"></i></span>
                        <span class="pc-mtext">Scan Absensi</span>
                    </a>
                </li>

                <li class="pc-item">
                    <a href="#!" class="pc-link">
                        <span class="pc-micon"><i class="ti ti-file-report"></i></span>
                        <span class="pc-mtext">Laporan</span>
                    </a>
                </li>

                <li class="pc-item pc-caption">
                    <label>Pengaturan</label>
                    <i class="ti ti-settings"></i>
                </li>

                <li class="pc-item">
                    <a href="#!" class="pc-link">
                        <span class="pc-micon"><i class="ti ti-building"></i></span>
                        <span class="pc-mtext">Unit Kerja</span>
                    </a>
                </li>

                <li class="pc-item">
                    <a href="#!" class="pc-link">
                        <span class="pc-micon"><i class="ti ti-briefcase"></i></span>
                        <span class="pc-mtext">Jabatan</span>
                    </a>
                </li>

                <li class="pc-item">
                    <a href="#!" class="pc-link">
                        <span class="pc-micon"><i class="ti ti-shield-lock"></i></span>
                        <span class="pc-mtext">Role & User</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
