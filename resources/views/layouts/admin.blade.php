<!DOCTYPE html>
<html lang="id">
<head>
    <title>@yield('title', 'YAPISTA HRIS')</title>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="YAPISTA HRIS">
    <meta name="keywords" content="YAPISTA, HRIS, Pegawai, Kehadiran, ID Card">
    <meta name="author" content="YAPISTA">

    <link rel="icon" href="{{ asset('assets/images/favicon.svg') }}" type="image/x-icon">

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" id="main-font-link">

    <link rel="stylesheet" href="{{ asset('assets/fonts/tabler-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/fonts/feather.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/fonts/fontawesome.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/fonts/material.css') }}">

    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}" id="main-style-link">
    <link rel="stylesheet" href="{{ asset('assets/css/style-preset.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/yapista-ui.css') }}">

    @stack('styles')
</head>

@php
    $layoutMode = trim($__env->yieldContent('layout-mode'));
    $isScannerFocusMode = $layoutMode === 'scanner';
    $isEmployeeOnboardingMode = $layoutMode === 'employee-onboarding';
    $bodyClass = match (true) {
        $isScannerFocusMode => 'scanner-focus-page',
        $isEmployeeOnboardingMode => 'employee-onboarding-page',
        Auth::user()?->isPegawai() => 'employee-app',
        default => '',
    };
@endphp

<body class="{{ $bodyClass }}" data-pc-preset="preset-1" data-pc-direction="ltr" data-pc-theme="light">

    <div class="loader-bg">
        <div class="loader-track">
            <div class="loader-fill"></div>
        </div>
    </div>

    @if ($isScannerFocusMode)
        <main class="scanner-focus-main">
            <div class="scanner-focus-shell">
                @yield('content')
            </div>
        </main>
    @elseif ($isEmployeeOnboardingMode)
        <main class="employee-onboarding-main">
            <header class="employee-onboarding-appbar">
                <div class="employee-onboarding-appbar-inner">
                    <a href="{{ route('pegawai.profile.wizard.index') }}" aria-label="Kembali ke langkah aktif onboarding">
                        <x-application-logo class="employee-onboarding-logo" image-class="img-fluid" />
                    </a>
                    <div class="employee-onboarding-brand-copy">
                        <strong>Onboarding Pegawai</strong>
                        <span>YAPISTA HRIS</span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="ms-auto">
                        @csrf
                        <button type="submit" class="btn btn-light-secondary btn-sm d-inline-flex align-items-center gap-2">
                            <i class="ti ti-logout" aria-hidden="true"></i>
                            <span>Keluar</span>
                        </button>
                    </form>
                </div>
            </header>
            <div class="employee-onboarding-shell">
                @yield('content')
            </div>
        </main>

        <x-confirm-action-modal />
    @else
        @include('partials.sidebar')
        @include('partials.header')

        <div class="pc-container">
            <div class="pc-content">
                <div class="app-content-shell">
                    @yield('content')
                </div>
            </div>
        </div>

        @include('partials.footer')

        <x-confirm-action-modal />

        @if (Auth::user()?->isPegawai())
            @include('partials.employee-bottom-nav')
        @endif
    @endif

    @stack('page-scripts')

    <script src="{{ asset('assets/js/plugins/popper.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/simplebar.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/js/fonts/custom-font.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/feather.min.js') }}"></script>

    @unless ($isScannerFocusMode || $isEmployeeOnboardingMode)
        <script src="{{ asset('assets/js/pcoded.js') }}"></script>
        <script>layout_change('light');</script>
        <script>change_box_container('false');</script>
        <script>layout_rtl_change('false');</script>
        <script>preset_change("preset-1");</script>
        <script>font_change("Public-Sans");</script>
    @endunless
    <script>document.documentElement.lang = 'id';</script>

    @stack('scripts')
</body>
</html>
