<!DOCTYPE html>
<html lang="en">
<head>
    <title>@yield('title', 'YAPISTA HRIS')</title>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="YAPISTA HRIS">
    <meta name="keywords" content="YAPISTA, HRIS, Pegawai, Absensi, ID Card">
    <meta name="author" content="YAPISTA">

    <link rel="icon" href="{{ asset('assets/images/favicon.svg') }}" type="image/x-icon">

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" id="main-font-link">

    <link rel="stylesheet" href="{{ asset('assets/fonts/tabler-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/fonts/feather.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/fonts/fontawesome.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/fonts/material.css') }}">

    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}" id="main-style-link">
    <link rel="stylesheet" href="{{ asset('assets/css/style-preset.css') }}">

    <style>
        .table-actions {
            display: inline-flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 0.35rem;
        }

        .table-actions form {
            margin: 0;
        }

        .table-actions .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.25rem;
            white-space: nowrap;
        }

        .table-actions .btn-icon {
            width: 32px;
            height: 32px;
            padding: 0;
        }

        .page-intro-card .card-body {
            padding: 1.35rem 1.5rem;
        }

        .page-intro-card h4,
        .page-intro-card h5 {
            letter-spacing: 0;
        }

        .summary-card .card-body {
            padding: 1rem 1.15rem;
        }

        .summary-card .avtar {
            flex: 0 0 auto;
        }

        .filter-card .form-label {
            font-size: 0.8125rem;
            font-weight: 600;
            color: #5b6b79;
        }

        .status-stack {
            display: inline-flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 0.35rem;
        }

        .data-meta {
            color: #5b6b79;
            font-size: 0.8125rem;
        }

        .empty-state {
            padding: 3rem 1rem;
            text-align: center;
        }

        .empty-state .avtar {
            margin-inline: auto;
            margin-bottom: 1rem;
        }

        .dropdown-menu form {
            margin: 0;
        }

        .dropdown-menu .dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.45rem;
        }

        @media (max-width: 767.98px) {
            .table-actions {
                justify-content: flex-start;
            }
        }
    </style>

    @stack('styles')
</head>

<body data-pc-preset="preset-1" data-pc-direction="ltr" data-pc-theme="light">

    <div class="loader-bg">
        <div class="loader-track">
            <div class="loader-fill"></div>
        </div>
    </div>

    @include('partials.sidebar')
    @include('partials.header')

    <div class="pc-container">
        <div class="pc-content">
            @yield('content')
        </div>
    </div>

    @include('partials.footer')

    @stack('page-scripts')

    <script src="{{ asset('assets/js/plugins/popper.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/simplebar.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/js/fonts/custom-font.js') }}"></script>
    <script src="{{ asset('assets/js/pcoded.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/feather.min.js') }}"></script>

    <script>layout_change('light');</script>
    <script>change_box_container('false');</script>
    <script>layout_rtl_change('false');</script>
    <script>preset_change("preset-1");</script>
    <script>font_change("Public-Sans");</script>

    @stack('scripts')
</body>
</html>
