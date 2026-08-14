@extends('layouts.admin')

@section('title', 'Profil Saya | YAPISTA HRIS')

@section('content')
    @php($dashboardRoute = Auth::user()?->isPanitia() ? 'scanner.dashboard' : 'dashboard')

    <x-page-header
        title="Profil Saya"
        subtitle="Kelola informasi login dan keamanan akun Anda."
        :breadcrumbs="[
            ['label' => 'Dashboard', 'url' => route($dashboardRoute)],
            ['label' => 'Profil Saya'],
        ]"
    />

    <div class="row g-3">
        <div class="col-xl-6">
            <section class="card h-100" aria-labelledby="profile-information-heading">
                <div class="card-header">
                    <div>
                        <h2 id="profile-information-heading" class="h5 mb-1">Informasi Login</h2>
                        <p class="text-muted mb-0">Nama akun dan email yang digunakan untuk masuk.</p>
                    </div>
                </div>
                <div class="card-body">
                    @if (session('status') === 'profile-updated')
                        <div class="alert alert-success">Informasi akun berhasil diperbarui.</div>
                    @endif

                    <form id="send-verification" method="POST" action="{{ route('verification.send') }}">@csrf</form>
                    <form method="POST" action="{{ route('profile.update') }}">
                        @csrf
                        @method('PATCH')

                        <div class="mb-3">
                            <label for="name" class="form-label">Nama</label>
                            <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" class="form-control @error('name') is-invalid @enderror" required autofocus autocomplete="name">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Login</label>
                            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" class="form-control @error('email') is-invalid @enderror" required autocomplete="username">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                            <div class="alert alert-warning py-2">
                                Email belum terverifikasi.
                                <button form="send-verification" class="btn btn-link p-0 align-baseline">Kirim ulang tautan verifikasi</button>.
                            </div>
                        @endif

                        <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy" aria-hidden="true"></i> Simpan Perubahan</button>
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
                            <input id="current_password" name="current_password" type="password" class="form-control @if ($errors->updatePassword->has('current_password')) is-invalid @endif" autocomplete="current-password">
                            @foreach ($errors->updatePassword->get('current_password') as $message)<div class="invalid-feedback">{{ $message }}</div>@endforeach
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Password Baru</label>
                            <input id="new_password" name="password" type="password" class="form-control @if ($errors->updatePassword->has('password')) is-invalid @endif" autocomplete="new-password">
                            @foreach ($errors->updatePassword->get('password') as $message)<div class="invalid-feedback">{{ $message }}</div>@endforeach
                        </div>
                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Konfirmasi Password Baru</label>
                            <input id="password_confirmation" name="password_confirmation" type="password" class="form-control @if ($errors->updatePassword->has('password_confirmation')) is-invalid @endif" autocomplete="new-password">
                            @foreach ($errors->updatePassword->get('password_confirmation') as $message)<div class="invalid-feedback">{{ $message }}</div>@endforeach
                        </div>

                        <button type="submit" class="btn btn-primary"><i class="ti ti-lock" aria-hidden="true"></i> Perbarui Password</button>
                    </form>
                </div>
            </section>
        </div>
    </div>

    <section class="card border-danger mt-3" aria-labelledby="delete-account-heading">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h2 id="delete-account-heading" class="h5 mb-1">Hapus Akun</h2>
                <p class="text-muted mb-0">Tindakan ini permanen dan akan menghapus akses akun Anda.</p>
            </div>
            <button type="button" class="btn btn-light-danger" data-bs-toggle="modal" data-bs-target="#delete-account-modal">
                <i class="ti ti-trash" aria-hidden="true"></i> Hapus Akun
            </button>
        </div>
    </section>

    <div class="modal fade" id="delete-account-modal" tabindex="-1" aria-labelledby="delete-account-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('profile.destroy') }}">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header">
                        <h2 class="modal-title h5" id="delete-account-modal-title">Hapus Akun?</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted">Masukkan password untuk mengonfirmasi penghapusan akun secara permanen.</p>
                        <label for="delete_account_password" class="form-label">Password</label>
                        <input id="delete_account_password" name="password" type="password" class="form-control @if ($errors->userDeletion->has('password')) is-invalid @endif" autocomplete="current-password">
                        @foreach ($errors->userDeletion->get('password') as $message)<div class="invalid-feedback">{{ $message }}</div>@endforeach
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Hapus Akun</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@if ($errors->userDeletion->isNotEmpty())
    @push('scripts')
        <script>
            bootstrap.Modal.getOrCreateInstance(document.getElementById('delete-account-modal')).show();
        </script>
    @endpush
@endif
