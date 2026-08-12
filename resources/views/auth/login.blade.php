<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <title>Login | YAPISTA HRIS</title>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Sistem Informasi Kepegawaian YAPISTA">
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
</head>

<body class="auth-login-page">
    <div class="loader-bg">
        <div class="loader-track">
            <div class="loader-fill"></div>
        </div>
    </div>

    <main class="auth-login-shell">
        <section class="auth-brand-panel" aria-label="YAPISTA HRIS">
            <div class="auth-brand-content">
                <x-application-logo class="auth-login-brand-logo" image-class="auth-login-brand-image" />
                <div class="auth-brand-copy">
                    <p class="auth-brand-eyebrow">Yayasan Pendidikan Ibnu Sina Batam</p>
                    <h1>Sistem Informasi Kepegawaian</h1>
                    <p>Kelola layanan kepegawaian YAPISTA secara aman dalam satu sistem.</p>
                </div>
            </div>
            <p class="auth-brand-footer mb-0">&copy; {{ date('Y') }} YAPISTA HRIS</p>
        </section>

        <section class="auth-form-panel">
            <div class="auth-login-form">
                <div class="mb-4">
                    <h2 class="auth-login-title">Selamat Datang</h2>
                    <p class="text-muted mb-0">Masuk untuk mengakses YAPISTA HRIS.</p>
                </div>

                @if (session('status'))
                    <div class="alert alert-success py-2" role="status">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" novalidate>
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            class="form-control @error('email') is-invalid @enderror"
                            placeholder="nama@contoh.com"
                            autocomplete="username"
                            required
                            autofocus
                        >
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group auth-password-group">
                            <input
                                id="password"
                                type="password"
                                name="password"
                                class="form-control @error('password') is-invalid @enderror"
                                placeholder="Masukkan password"
                                autocomplete="current-password"
                                required
                            >
                            <button
                                type="button"
                                class="btn btn-light-secondary auth-password-toggle"
                                id="password-toggle"
                                aria-label="Tampilkan password"
                                aria-controls="password"
                                aria-pressed="false"
                            >
                                <i class="ti ti-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                        @error('password')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="auth-session-options">
                        <div class="form-check mb-0">
                            <input
                                id="remember_me"
                                class="form-check-input input-primary"
                                type="checkbox"
                                name="remember"
                                @checked(old('remember'))
                            >
                            <label class="form-check-label text-muted" for="remember_me">Ingat saya</label>
                        </div>

                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="link-primary f-w-500">
                                Lupa password?
                            </a>
                        @endif
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary auth-login-submit">Masuk</button>
                    </div>
                </form>

                <p class="auth-form-footer mb-0">Gunakan akun yang telah diberikan oleh HR/Admin.</p>
            </div>
        </section>
    </main>

    <script src="{{ asset('assets/js/plugins/popper.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/bootstrap.min.js') }}"></script>
    <script>
        document.getElementById('password-toggle')?.addEventListener('click', function () {
            const password = document.getElementById('password');
            const showing = password.type === 'text';

            password.type = showing ? 'password' : 'text';
            this.setAttribute('aria-pressed', showing ? 'false' : 'true');
            this.setAttribute('aria-label', showing ? 'Tampilkan password' : 'Sembunyikan password');
            this.querySelector('i')?.classList.toggle('ti-eye', showing);
            this.querySelector('i')?.classList.toggle('ti-eye-off', ! showing);
        });
    </script>
</body>
</html>
