@extends('layouts.admin')

@section('title', 'Undangan Registrasi Pegawai | YAPISTA HRIS')

@section('content')
    @php
        $statuses = [
            'unused' => 'Unused',
            'used' => 'Used',
            'expired' => 'Expired',
            'revoked' => 'Revoked',
        ];
        $statusClasses = [
            'unused' => 'bg-light-primary text-primary',
            'used' => 'bg-light-success text-success',
            'expired' => 'bg-light-warning text-warning',
            'revoked' => 'bg-light-danger text-danger',
        ];
    @endphp

    <div class="page-header">
        <div class="page-block">
            <div class="row align-items-center">
                <div class="col-md-12">
                    <div class="page-header-title">
                        <h5 class="m-b-10">Undangan Registrasi Pegawai</h5>
                    </div>

                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item" aria-current="page">Undangan Registrasi</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if (session('invitation_code') && session('invitation_link'))
        <div class="alert alert-info">
            <div class="fw-semibold">Kode baru: {{ session('invitation_code') }}</div>
            <div class="text-break">{{ session('invitation_link') }}</div>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Daftar Undangan</h5>
        </div>

        <div class="card-body">
            <form method="GET" action="{{ route('invitations.index') }}" class="row g-2 mb-3">
                <div class="col-md-5">
                    <input
                        type="search"
                        name="search"
                        value="{{ $search }}"
                        class="form-control"
                        placeholder="Cari nama, email, HP, atau kode undangan"
                    >
                </div>

                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">Semua status</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <select name="institution_id" class="form-select">
                        <option value="">Semua unit kerja</option>
                        @foreach ($institutions as $institution)
                            <option value="{{ $institution->id }}" @selected((string) request('institution_id') === (string) $institution->id)>
                                {{ $institution->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-1">
                    <button type="submit" class="btn btn-outline-primary w-100">
                        <i class="ti ti-filter"></i>
                    </button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover table-borderless mb-0">
                    <thead>
                        <tr>
                            <th style="width: 70px;">No</th>
                            <th>Nama Pegawai</th>
                            <th>Unit Kerja</th>
                            <th>Jabatan</th>
                            <th>Kode Undangan</th>
                            <th>Link Register</th>
                            <th>Status</th>
                            <th>Expired At</th>
                            <th>Created By</th>
                            <th class="text-end" style="width: 160px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($invitations as $invitation)
                            @php
                                $registerLink = route('invitation.register.show', $invitation->invitation_code);
                            @endphp
                            <tr>
                                <td>{{ $invitations->firstItem() + $loop->index }}</td>
                                <td>{{ $invitation->employee?->full_name ?? '-' }}</td>
                                <td>{{ $invitation->employee?->institution?->name ?? '-' }}</td>
                                <td>{{ $invitation->employee?->position?->name ?? '-' }}</td>
                                <td><code>{{ $invitation->invitation_code }}</code></td>
                                <td class="text-break">{{ $registerLink }}</td>
                                <td>
                                    <span class="badge {{ $statusClasses[$invitation->status] ?? 'bg-light-secondary text-secondary' }}">
                                        {{ $statuses[$invitation->status] ?? $invitation->status }}
                                    </span>
                                </td>
                                <td>{{ $invitation->expired_at?->format('d M Y H:i') ?? '-' }}</td>
                                <td>{{ $invitation->creator?->name ?? '-' }}</td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-light-primary js-copy-link" data-link="{{ $registerLink }}">
                                        <i class="ti ti-copy"></i>
                                        Copy
                                    </button>

                                    @if ($invitation->isUnused())
                                        <form action="{{ route('invitations.revoke', $invitation) }}" method="POST" class="d-inline" onsubmit="return confirm('Batalkan undangan ini?')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-light-danger">
                                                <i class="ti ti-ban"></i>
                                                Revoke
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted">Belum ada undangan registrasi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $invitations->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('.js-copy-link').forEach((button) => {
            button.addEventListener('click', async () => {
                await navigator.clipboard.writeText(button.dataset.link);
                button.innerText = 'Copied';
            });
        });
    </script>
@endpush
