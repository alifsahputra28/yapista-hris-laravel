@extends('layouts.admin')

@section('title', 'Manajemen User | YAPISTA HRIS')

@push('styles')
    <style>
        .user-management-table { min-width: 860px; }
        .user-management-table .dropdown-menu { min-width: 220px; }
        @media (max-width: 575.98px) {
            .user-filter-actions > * { width: 100%; }
            .user-management-card .card-header { padding-inline: 1rem; }
        }
    </style>
@endpush

@section('content')
    <x-page-header
        title="Manajemen User"
        subtitle="Kelola akun autentikasi, role, hubungan pegawai, dan status akses pengguna."
        :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Manajemen User']]"
    >
        <x-slot:actions>
            <a href="{{ route('users.create') }}" class="btn btn-primary"><i class="ti ti-user-plus" aria-hidden="true"></i> Tambah User</a>
        </x-slot:actions>
    </x-page-header>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any() && ! $errors->has('password'))
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="card filter-card">
        <div class="card-header"><h5 class="mb-0">Filter User</h5></div>
        <div class="card-body">
            <form method="GET" action="{{ route('users.index') }}" class="row g-3 align-items-end">
                <div class="col-12 col-lg-4">
                    <label for="search" class="form-label">Cari User</label>
                    <div class="filter-search-wrap">
                        <i class="ti ti-search" aria-hidden="true"></i>
                        <input id="search" type="search" name="search" value="{{ $search }}" class="form-control" placeholder="Nama, email, nama pegawai, atau NUP">
                    </div>
                </div>
                <div class="col-6 col-lg-2">
                    <label for="role" class="form-label">Role</label>
                    <select id="role" name="role" class="form-select">
                        <option value="">Semua role</option>
                        @foreach ($roleLabels as $value => $label)
                            <option value="{{ $value }}" @selected($role === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label for="status" class="form-label">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">Semua status</option>
                        @foreach ($statusLabels as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-4 col-lg-1">
                    <label for="per_page" class="form-label">Tampil</label>
                    <select id="per_page" name="per_page" class="form-select">
                        @foreach ([15, 25, 50] as $size)
                            <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-8 col-lg-3 d-flex flex-wrap gap-2 user-filter-actions">
                    <button type="submit" class="btn btn-primary flex-fill"><i class="ti ti-filter" aria-hidden="true"></i> Terapkan</button>
                    @if (request()->filled('search') || request()->filled('role') || request()->filled('status') || request('per_page', 15) != 15)
                        <a href="{{ route('users.index') }}" class="btn btn-light-secondary">Reset</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card user-management-card">
        <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <h5 class="mb-0">Daftar User</h5>
            <span class="text-muted small">Menampilkan {{ $users->firstItem() ?? 0 }}–{{ $users->lastItem() ?? 0 }} dari {{ $users->total() }} user</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 user-management-table">
                    <thead>
                        <tr>
                            <th class="ps-4" style="width: 70px;">No</th>
                            <th>Nama</th>
                            <th>NUP</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Pegawai Terkait</th>
                            <th>Status</th>
                            <th class="text-end pe-4" style="width: 100px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $managedUser)
                            <tr>
                                <td class="ps-4">{{ $users->firstItem() + $loop->index }}</td>
                                <td class="fw-semibold">{{ $managedUser->name }}</td>
                                <td>{{ $managedUser->employee?->employee_number ?: '—' }}</td>
                                <td>{{ $managedUser->email ?: '—' }}</td>
                                <td><span class="badge bg-light-primary text-primary">{{ $roleLabels[$managedUser->role] ?? $managedUser->role }}</span></td>
                                <td>
                                    @if ($managedUser->employee)
                                        <div class="fw-semibold">{{ $managedUser->employee->full_name }}</div>
                                        <div class="data-meta">NUP {{ $managedUser->employee->employee_number ?: '-' }} · {{ $managedUser->employee->institution?->name ?: 'Unit belum tersedia' }}</div>
                                    @else
                                        <span class="text-muted">Belum terhubung</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $managedUser->isActive() ? 'bg-light-success text-success' : 'bg-light-secondary text-secondary' }}">
                                        {{ $statusLabels[$managedUser->status] ?? $managedUser->status }}
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light-secondary btn-icon" type="button" data-bs-toggle="dropdown" data-user-action-toggle aria-expanded="false" aria-haspopup="true" aria-label="Aksi untuk {{ $managedUser->name }}">
                                            <i class="ti ti-dots-vertical" aria-hidden="true"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <a href="{{ route('users.edit', $managedUser) }}" class="dropdown-item"><i class="ti ti-edit me-2"></i>Edit User</a>
                                            <button
                                                type="button"
                                                class="dropdown-item"
                                                data-reset-password
                                                data-user-name="{{ $managedUser->name }}"
                                                data-action="{{ route('users.reset-password', $managedUser) }}"
                                            ><i class="ti ti-key me-2"></i>Reset Password</button>
                                            <div class="dropdown-divider"></div>
                                            <form action="{{ route('users.toggle-status', $managedUser) }}" method="POST"
                                                @if ($managedUser->isActive())
                                                    data-confirm-title="Nonaktifkan User?"
                                                    data-confirm-message="User tidak dapat login setelah dinonaktifkan. Seluruh histori data tetap tersimpan."
                                                @else
                                                    data-confirm-title="Aktifkan Kembali User?"
                                                    data-confirm-message="User akan dapat login kembali menggunakan kredensialnya."
                                                @endif
                                            >
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="dropdown-item {{ $managedUser->isActive() ? 'text-danger' : 'text-success' }}">
                                                    <i class="ti {{ $managedUser->isActive() ? 'ti-user-off' : 'ti-user-check' }} me-2"></i>
                                                    {{ $managedUser->isActive() ? 'Nonaktifkan' : 'Aktifkan Kembali' }}
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state">
                                        <div class="avtar avtar-l bg-light-secondary text-secondary"><i class="ti ti-users-off f-28"></i></div>
                                        <h5 class="mb-1">Tidak ada user yang sesuai.</h5>
                                        <p class="text-muted mb-3">Ubah filter atau tambahkan user baru.</p>
                                        <a href="{{ route('users.create') }}" class="btn btn-primary"><i class="ti ti-user-plus"></i> Tambah User</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($users->hasPages())
            <div class="card-footer">{{ $users->links() }}</div>
        @endif
    </div>

    <div class="modal fade" id="reset-password-modal" tabindex="-1" aria-labelledby="reset-password-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mx-3 mx-sm-auto">
            <div class="modal-content">
                <form method="POST" action="" data-reset-password-form data-route-template="{{ url('/users/__USER__/password') }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="reset_user_id" value="{{ old('reset_user_id') }}">
                    <div class="modal-header">
                        <div>
                            <h2 class="modal-title h5 mb-1" id="reset-password-modal-title">Reset Password</h2>
                            <div class="text-muted small" data-reset-password-user></div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        @if ($errors->has('password'))
                            <div class="alert alert-danger">{{ $errors->first('password') }}</div>
                        @endif
                        <div class="mb-3">
                            <label for="reset_password" class="form-label">Password Baru <span class="text-danger">*</span></label>
                            <input id="reset_password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" minlength="12" autocomplete="new-password" required>
                            <div class="form-text">Minimal 12 karakter.</div>
                        </div>
                        <div>
                            <label for="reset_password_confirmation" class="form-label">Konfirmasi Password Baru <span class="text-danger">*</span></label>
                            <input id="reset_password_confirmation" type="password" name="password_confirmation" class="form-control" minlength="12" autocomplete="new-password" required>
                        </div>
                    </div>
                    <div class="modal-footer d-flex flex-column-reverse flex-sm-row">
                        <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="ti ti-key"></i> Perbarui Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-user-action-toggle]').forEach((toggle) => {
                bootstrap.Dropdown.getOrCreateInstance(toggle, {
                    boundary: 'viewport',
                    popperConfig(defaultConfig) {
                        return {
                            ...defaultConfig,
                            strategy: 'fixed',
                        };
                    },
                });
            });

            const modalElement = document.getElementById('reset-password-modal');
            const form = modalElement?.querySelector('[data-reset-password-form]');
            const userLabel = modalElement?.querySelector('[data-reset-password-user]');
            const userIdInput = modalElement?.querySelector('input[name="reset_user_id"]');

            document.querySelectorAll('[data-reset-password]').forEach((button) => {
                button.addEventListener('click', () => {
                    form.action = button.dataset.action;
                    userLabel.textContent = button.dataset.userName;
                    userIdInput.value = button.dataset.action.split('/').slice(-2, -1)[0];
                    bootstrap.Modal.getOrCreateInstance(modalElement).show();
                });
            });

            @if ($errors->has('password') && old('reset_user_id'))
                form.action = form.dataset.routeTemplate.replace('__USER__', @js(old('reset_user_id')));
                userLabel.textContent = 'Perbaiki data password lalu kirim kembali.';
                bootstrap.Modal.getOrCreateInstance(modalElement).show();
            @endif
        });
    </script>
@endpush
