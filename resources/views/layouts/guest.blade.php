<!DOCTYPE html>
<html lang="id">
<head>
    <title>YAPISTA HRIS</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Sistem Informasi Kepegawaian YAPISTA">

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
                    <p>Layanan akun YAPISTA HRIS yang aman dan terintegrasi.</p>
                </div>
            </div>
            <p class="auth-brand-footer mb-0">&copy; {{ date('Y') }} YAPISTA HRIS</p>
        </section>

        <section class="auth-form-panel">
            <div class="auth-login-form">
                {{ $slot }}
            </div>
        </section>
    </main>

    <script src="{{ asset('assets/js/plugins/popper.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/bootstrap.min.js') }}"></script>
</body>
</html>
