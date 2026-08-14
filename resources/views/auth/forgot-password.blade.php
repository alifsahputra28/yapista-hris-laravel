<x-guest-layout>
    <div class="mb-4">
        <h1 class="auth-login-title">Lupa Password</h1>
        <p class="text-muted mb-0">Masukkan email akun Anda. Kami akan mengirim tautan untuk membuat password baru.</p>
    </div>

    @if (session('status'))
        <div class="alert alert-success py-2" role="status">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" autocomplete="username" required autofocus>
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary">Kirim Tautan Reset Password</button>
            <a href="{{ route('login') }}" class="btn btn-light-secondary">Kembali ke Login</a>
        </div>
    </form>
</x-guest-layout>
