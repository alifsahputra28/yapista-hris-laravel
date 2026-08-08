<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <title>Registrasi Pegawai YAPISTA</title>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Registrasi Pegawai YAPISTA">

    <link rel="icon" href="{{ asset('assets/images/favicon.svg') }}" type="image/x-icon">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" id="main-font-link">
    <link rel="stylesheet" href="{{ asset('assets/fonts/tabler-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}" id="main-style-link">
    <link rel="stylesheet" href="{{ asset('assets/css/style-preset.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/yapista-ui.css') }}">
</head>

<body>
    <div class="loader-bg">
        <div class="loader-track">
            <div class="loader-fill"></div>
        </div>
    </div>

    <div class="auth-main">
        <div class="auth-wrapper v3">
            <div class="auth-form">
                <div class="auth-header">
                    <a href="{{ url('/') }}">
                        <x-application-logo class="auth-brand-logo" />
                    </a>
                </div>

                <div class="card my-5">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <h3 class="mb-2"><b>Registrasi Pegawai YAPISTA</b></h3>
                            <p class="text-muted mb-0">Buat akun pegawai menggunakan kode undangan resmi dari HR/Admin.</p>
                        </div>

                        @if ($error)
                            <div class="alert alert-danger" role="alert">
                                {{ $error }}
                            </div>

                            <a href="{{ route('login') }}" class="btn btn-primary w-100">Kembali ke Login</a>
                        @else
                            @if ($errors->any())
                                <div class="alert alert-danger" role="alert">
                                    {{ $errors->first() }}
                                </div>
                            @endif

                            <div class="alert alert-light border d-flex gap-3 align-items-start">
                                <div class="avtar avtar-s bg-light-primary text-primary">
                                    <i class="ti ti-user"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold">{{ $employee->full_name }}</div>
                                    <div>{{ $employee->institution?->name ?? '-' }}</div>
                                    <div class="text-muted">{{ $employee->position?->name ?? '-' }}</div>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('invitation.register.store', $invitation->invitation_code) }}">
                                @csrf

                                <div class="form-group mb-3">
                                    <label for="name" class="form-label">Nama</label>
                                    <input
                                        id="name"
                                        type="text"
                                        name="name"
                                        value="{{ old('name', $employee->full_name) }}"
                                        class="form-control @error('name') is-invalid @enderror"
                                        placeholder="Nama lengkap"
                                        required
                                        autofocus
                                    >

                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input
                                        id="email"
                                        type="email"
                                        name="email"
                                        value="{{ old('email', $employee->email) }}"
                                        class="form-control @error('email') is-invalid @enderror"
                                        placeholder="email@domain.com"
                                        required
                                        autocomplete="username"
                                    >

                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input
                                        id="password"
                                        type="password"
                                        name="password"
                                        class="form-control @error('password') is-invalid @enderror"
                                        placeholder="Minimal 8 karakter"
                                        required
                                        autocomplete="new-password"
                                    >

                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group mb-4">
                                    <label for="password_confirmation" class="form-label">Konfirmasi Password</label>
                                    <input
                                        id="password_confirmation"
                                        type="password"
                                        name="password_confirmation"
                                        class="form-control"
                                        placeholder="Ulangi password"
                                        required
                                        autocomplete="new-password"
                                    >
                                </div>

                                <button type="submit" class="btn btn-primary w-100">Daftar</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('assets/js/plugins/popper.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/js/pcoded.js') }}"></script>
    <script>layout_change('light');</script>
    <script>change_box_container('false');</script>
    <script>layout_rtl_change('false');</script>
    <script>preset_change("preset-1");</script>
    <script>font_change("Public-Sans");</script>
</body>
</html>
