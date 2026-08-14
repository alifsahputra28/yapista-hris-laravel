<x-guest-layout>
    <div class="mb-4">
        <h1 class="auth-login-title">Verifikasi Email</h1>
        <p class="text-muted mb-0">Buka tautan verifikasi yang telah dikirim ke email Anda sebelum melanjutkan.</p>
    </div>

    @if (session('status') === 'verification-link-sent')
        <div class="alert alert-success py-2" role="status">Tautan verifikasi baru telah dikirim.</div>
    @endif

    <div class="d-grid gap-2">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn btn-primary w-100">Kirim Ulang Email Verifikasi</button>
        </form>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-light-secondary w-100">Keluar</button>
        </form>
    </div>
</x-guest-layout>
