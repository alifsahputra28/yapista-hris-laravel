<x-guest-layout>
    <div class="mb-4">
        <h1 class="auth-login-title">Konfirmasi Password</h1>
        <p class="text-muted mb-0">Masukkan kembali password Anda untuk melanjutkan ke area aman.</p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf
        <div class="mb-4">
            <label for="password" class="form-label">Password</label>
            <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="current-password" autofocus>
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <button type="submit" class="btn btn-primary w-100">Konfirmasi</button>
    </form>
</x-guest-layout>
