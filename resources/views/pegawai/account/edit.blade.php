@extends('layouts.admin')

@section('title', 'Keamanan Akun | YAPISTA HRIS')

@section('content')
    <x-page-header
        title="Keamanan Akun"
        subtitle="Kelola email login dan password Anda."
        :breadcrumbs="[
            ['label' => 'Beranda', 'url' => route('pegawai.dashboard')],
            ['label' => 'Akun', 'url' => route('pegawai.profile.show')],
            ['label' => 'Keamanan Akun'],
        ]"
    >
        <x-slot:actions>
            <a href="{{ route('pegawai.profile.show') }}" class="btn btn-light-secondary">
                <i class="ti ti-arrow-left" aria-hidden="true"></i> Kembali
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="row g-3">
        <div class="col-xl-6">
            <section class="card h-100" aria-labelledby="account-information-heading">
                <div class="card-header">
                    <div>
                        <h2 id="account-information-heading" class="h5 mb-1">Informasi Login</h2>
                        <p class="text-muted mb-0">Nama akun dan email yang digunakan untuk masuk.</p>
                    </div>
                </div>
                <div class="card-body">
                    @if (session('status') === 'profile-updated')
                        <div class="alert alert-success">Informasi akun berhasil diperbarui.</div>
                    @endif

                    <form method="POST" action="{{ route('profile.update') }}">
                        @csrf
                        @method('PATCH')

                        <div class="mb-3">
                            <label for="account_name" class="form-label">Nama</label>
                            <input id="account_name" type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control @error('name') is-invalid @enderror" required autocomplete="name">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="account_email" class="form-label">Email Login</label>
                            <input id="account_email" type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control @error('email') is-invalid @enderror" required autocomplete="username">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy" aria-hidden="true"></i> Simpan
                        </button>
                    </form>
                </div>
            </section>
        </div>

        <div class="col-xl-6">
            <section class="card h-100" aria-labelledby="password-heading">
                <div class="card-header">
                    <div>
                        <h2 id="password-heading" class="h5 mb-1">Ubah Password</h2>
                        <p class="text-muted mb-0">Gunakan password yang kuat dan tidak dipakai di layanan lain.</p>
                    </div>
                </div>
                <div class="card-body">
                    @if (session('status') === 'password-updated')
                        <div class="alert alert-success">Password berhasil diperbarui.</div>
                    @endif

                    <form method="POST" action="{{ route('password.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="current_password" class="form-label">Password Saat Ini</label>
                            <input id="current_password" type="password" name="current_password" class="form-control @if ($errors->updatePassword->has('current_password')) is-invalid @endif" autocomplete="current-password">
                            @foreach ($errors->updatePassword->get('current_password') as $message)<div class="invalid-feedback">{{ $message }}</div>@endforeach
                        </div>

                        <div class="mb-3">
                            <label for="new_password" class="form-label">Password Baru</label>
                            <input id="new_password" type="password" name="password" class="form-control @if ($errors->updatePassword->has('password')) is-invalid @endif" autocomplete="new-password">
                            @foreach ($errors->updatePassword->get('password') as $message)<div class="invalid-feedback">{{ $message }}</div>@endforeach
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Konfirmasi Password Baru</label>
                            <input id="password_confirmation" type="password" name="password_confirmation" class="form-control @if ($errors->updatePassword->has('password_confirmation')) is-invalid @endif" autocomplete="new-password">
                            @foreach ($errors->updatePassword->get('password_confirmation') as $message)<div class="invalid-feedback">{{ $message }}</div>@endforeach
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-lock" aria-hidden="true"></i> Perbarui Password
                        </button>
                    </form>
                </div>
            </section>
        </div>
    </div>
@endsection
