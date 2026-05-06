@php
    $dashboardRoute = match (Auth::user()?->role) {
        'panitia' => 'scanner.dashboard',
        'pegawai' => 'pegawai.dashboard',
        default => 'dashboard',
    };
@endphp

<footer class="pc-footer">
    <div class="footer-wrapper container-fluid">
        <div class="row">
            <div class="col-sm my-1">
                <p class="m-0">
                    YAPISTA HRIS &copy; {{ date('Y') }} - Sistem Pendataan Pegawai dan Absensi Kegiatan
                </p>
            </div>

            <div class="col-auto my-1">
                <ul class="list-inline footer-link mb-0">
                    <li class="list-inline-item">
                        <a href="{{ route($dashboardRoute) }}">Dashboard</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</footer>
