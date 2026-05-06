<header class="pc-header">
    <div class="header-wrapper">
        <div class="me-auto pc-mob-drp">
            <ul class="list-unstyled">
                <li class="pc-h-item pc-sidebar-collapse">
                    <a href="#" class="pc-head-link ms-0" id="sidebar-hide">
                        <i class="ti ti-menu-2"></i>
                    </a>
                </li>

                <li class="pc-h-item pc-sidebar-popup">
                    <a href="#" class="pc-head-link ms-0" id="mobile-collapse">
                        <i class="ti ti-menu-2"></i>
                    </a>
                </li>

                <li class="pc-h-item d-none d-md-inline-flex">
                    <form class="header-search">
                        <i data-feather="search" class="icon-search"></i>
                        <input type="search" class="form-control" placeholder="Cari data pegawai...">
                    </form>
                </li>
            </ul>
        </div>

        <div class="ms-auto">
            <ul class="list-unstyled">
                <li class="dropdown pc-h-item">
                    <a class="pc-head-link dropdown-toggle arrow-none me-0"
                       data-bs-toggle="dropdown"
                       href="#"
                       role="button"
                       aria-haspopup="false"
                       aria-expanded="false">
                        <i class="ti ti-bell"></i>
                    </a>

                    <div class="dropdown-menu dropdown-notification dropdown-menu-end pc-h-dropdown">
                        <div class="dropdown-header d-flex align-items-center justify-content-between">
                            <h5 class="m-0">Notifikasi</h5>
                            <a href="#!" class="pc-head-link bg-transparent">
                                <i class="ti ti-x text-danger"></i>
                            </a>
                        </div>

                        <div class="dropdown-divider"></div>

                        <div class="dropdown-header px-0 text-wrap header-notification-scroll position-relative" style="max-height: calc(100vh - 215px)">
                            <div class="list-group list-group-flush w-100">
                                <a class="list-group-item list-group-item-action">
                                    <div class="d-flex">
                                        <div class="flex-shrink-0">
                                            <div class="avtar avtar-s rounded-circle bg-light-primary">
                                                <i class="ti ti-user-plus"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-2">
                                            <p class="text-body mb-1">Pegawai baru menunggu verifikasi.</p>
                                            <span class="text-muted">Baru saja</span>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <div class="dropdown-divider"></div>

                        <div class="text-center py-2">
                            <a href="#!" class="link-primary">Lihat semua</a>
                        </div>
                    </div>
                </li>

                <li class="dropdown pc-h-item header-user-profile">
                    <a class="pc-head-link dropdown-toggle arrow-none me-0"
                       data-bs-toggle="dropdown"
                       href="#"
                       role="button"
                       aria-haspopup="false"
                       data-bs-auto-close="outside"
                       aria-expanded="false">
                        <img src="{{ asset('assets/images/user/avatar-2.jpg') }}" alt="user-image" class="user-avtar">
                        <span>{{ Auth::user()->name ?? 'Admin YAPISTA' }}</span>
                    </a>

                    <div class="dropdown-menu dropdown-user-profile dropdown-menu-end pc-h-dropdown">
                        <div class="dropdown-header">
                            <div class="d-flex mb-1">
                                <div class="flex-shrink-0">
                                    <img src="{{ asset('assets/images/user/avatar-2.jpg') }}" alt="user-image" class="user-avtar wid-35">
                                </div>

                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-1">{{ Auth::user()->name ?? 'Admin YAPISTA' }}</h6>
                                    <span>Administrator</span>
                                </div>
                            </div>
                        </div>

                        <div class="tab-content">
                            <a href="{{ route('profile.edit') }}" class="dropdown-item">
                                <i class="ti ti-user"></i>
                                <span>Profil Saya</span>
                            </a>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item">
                                    <i class="ti ti-power"></i>
                                    <span>Logout</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</header>