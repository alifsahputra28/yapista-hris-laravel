<!DOCTYPE html>
<html lang="id">
<head>
    <title>@yield('code') | YAPISTA HRIS</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="{{ asset('assets/images/favicon.svg') }}" type="image/x-icon">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="{{ asset('assets/fonts/tabler-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style-preset.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/yapista-ui.css') }}">
</head>
<body class="auth-login-page">
    <main class="auth-login-shell">
        <section class="auth-brand-panel" aria-label="YAPISTA HRIS">
            <div class="auth-brand-content">
                <x-application-logo class="auth-login-brand-logo" image-class="auth-login-brand-image" />
                <div class="auth-brand-copy">
                    <p class="auth-brand-eyebrow">Yayasan Pendidikan Ibnu Sina Batam</p>
                    <h1>Sistem Informasi Kepegawaian</h1>
                    <p>Layanan YAPISTA HRIS tetap melindungi akses dan data Anda.</p>
                </div>
            </div>
        </section>
        <section class="auth-form-panel">
            <div class="auth-login-form">
                <p class="text-primary fw-semibold mb-2">Error @yield('code')</p>
                <h1 class="auth-login-title">@yield('heading')</h1>
                <p class="text-muted mb-4">@yield('message')</p>
                <a href="{{ url('/') }}" class="btn btn-primary">Kembali ke Beranda</a>
            </div>
        </section>
    </main>
</body>
</html>
