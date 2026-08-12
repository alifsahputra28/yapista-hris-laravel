@extends('layouts.admin')

@section('title', 'Undangan Registrasi Pegawai | YAPISTA HRIS')

@section('content')
    @php
        $statuses = [
            'unused' => ['label' => 'Belum Digunakan', 'class' => 'bg-light-primary text-primary'],
            'used' => ['label' => 'Sudah Digunakan', 'class' => 'bg-light-success text-success'],
            'expired' => ['label' => 'Kedaluwarsa', 'class' => 'bg-light-warning text-warning'],
            'revoked' => ['label' => 'Dibatalkan', 'class' => 'bg-light-danger text-danger'],
        ];
        $summaryCards = [
            ['label' => 'Belum Digunakan', 'value' => $unusedInvitations ?? 0, 'icon' => 'ti-mail', 'class' => 'bg-light-primary text-primary'],
            ['label' => 'Sudah Digunakan', 'value' => $usedInvitations ?? 0, 'icon' => 'ti-mail-check', 'class' => 'bg-light-success text-success'],
            ['label' => 'Kedaluwarsa', 'value' => $expiredInvitations ?? 0, 'icon' => 'ti-clock-exclamation', 'class' => 'bg-light-warning text-warning'],
            ['label' => 'Dibatalkan', 'value' => $revokedInvitations ?? 0, 'icon' => 'ti-ban', 'class' => 'bg-light-danger text-danger'],
        ];
        $activeInstitution = $institutions->firstWhere('id', (int) request('institution_id'))?->name;
        $hasActiveFilters = request()->filled('search') || request()->filled('status') || request()->filled('institution_id');
        $activeFilterCount = collect(['status', 'institution_id'])->filter(fn ($key) => request()->filled($key))->count();
    @endphp

    <x-page-header
        title="Undangan Registrasi Pegawai"
        subtitle="Pantau kode undangan, link registrasi, masa berlaku, dan status pemakaian pegawai."
        :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Undangan Registrasi']]"
    >
        <x-slot:actions>
            <a href="{{ route('employees.index') }}" class="btn btn-primary"><i class="ti ti-users" aria-hidden="true"></i> Data Pegawai</a>
        </x-slot:actions>
    </x-page-header>

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

    <div class="row g-4 mb-4">
        @foreach ($summaryCards as $card)
            <div class="col-md-6 col-xl-3">
                <div class="card summary-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <div class="avtar avtar-s {{ $card['class'] }}">
                                <i class="ti {{ $card['icon'] }} f-20"></i>
                            </div>
                            <div>
                                <div class="text-muted small">{{ $card['label'] }}</div>
                                <h4 class="mb-0">{{ number_format($card['value']) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card filter-card">
        <div class="card-header">
            <h5 class="mb-0">Filter Undangan</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('invitations.index') }}" class="row g-3 align-items-end">
                <div class="col-lg-5">
                    <label for="search" class="form-label">Cari Undangan</label>
                    <div class="filter-search-wrap"><i class="ti ti-search" aria-hidden="true"></i><input id="search" type="search" name="search" value="{{ $search }}" class="form-control" placeholder="Cari nama, email, HP, atau kode undangan..." aria-label="Cari undangan pegawai"></div>
                </div>
                <div class="col-12 d-lg-none"><button class="btn btn-light-primary w-100" type="button" data-bs-toggle="collapse" data-bs-target=".invitation-mobile-filter" aria-expanded="{{ $activeFilterCount ? 'true' : 'false' }}"><i class="ti ti-adjustments-horizontal" aria-hidden="true"></i> Filter @if ($activeFilterCount)({{ $activeFilterCount }})@endif</button></div>
                <div class="col-md-6 col-lg-3 collapse d-lg-block invitation-mobile-filter {{ $activeFilterCount ? 'show' : '' }}">
                    <label for="status" class="form-label">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">Semua status</option>
                        @foreach ($statuses as $value => $status)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $status['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 col-lg-3 collapse d-lg-block invitation-mobile-filter {{ $activeFilterCount ? 'show' : '' }}">
                    <label for="institution_id" class="form-label">Unit Kerja</label>
                    <select id="institution_id" name="institution_id" class="form-select">
                        <option value="">Semua unit kerja</option>
                        @foreach ($institutions as $institution)
                            <option value="{{ $institution->id }}" @selected((string) request('institution_id') === (string) $institution->id)>
                                {{ $institution->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-1 filter-primary-actions collapse d-lg-block invitation-mobile-filter {{ $activeFilterCount ? 'show' : '' }}">
                    <button type="submit" class="btn btn-primary w-100" title="Terapkan Filter"><i class="ti ti-filter" aria-hidden="true"></i><span class="d-lg-none">Terapkan Filter</span></button>
                </div>
            </form>
            @if ($hasActiveFilters)
                <div class="filter-secondary-row justify-content-end"><a href="{{ route('invitations.index') }}" class="btn btn-sm btn-link text-muted">Reset semua</a></div>
                <div class="active-filter-summary" aria-label="Filter aktif"><span class="active-filter-label">Filter aktif:</span>
                    @if (request('status'))<x-active-filter-chip label="Status" :value="$statuses[request('status')]['label'] ?? request('status')" :url="route('invitations.index', request()->except('status', 'page'))" />@endif
                    @if ($activeInstitution)<x-active-filter-chip label="Unit" :value="$activeInstitution" :url="route('invitations.index', request()->except('institution_id', 'page'))" />@endif
                </div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <h5 class="mb-0">Daftar Undangan</h5>
                <span class="text-muted small">{{ $invitations->total() }} data berdasarkan filter saat ini</span>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4" style="width: 70px;">No</th>
                            <th>Pegawai</th>
                            <th>Kode & Link</th>
                            <th>Status</th>
                            <th>Masa Berlaku</th>
                            <th>Dibuat Oleh</th>
                            <th class="text-end pe-4" style="width: 160px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($invitations as $invitation)
                            @php
                                $registerLink = route('invitation.register.show', $invitation->invitation_code);
                                $status = $statuses[$invitation->status] ?? ['label' => $invitation->status, 'class' => 'bg-light-secondary text-secondary'];
                            @endphp
                            <tr>
                                <td class="ps-4">{{ $invitations->firstItem() + $loop->index }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $invitation->employee?->full_name ?? '-' }}</div>
                                    <div class="data-meta">{{ $invitation->employee?->institution?->name ?? '-' }}</div>
                                    <div class="data-meta">{{ $invitation->employee?->position?->name ?? '-' }}</div>
                                </td>
                                <td style="min-width: 260px;">
                                    <code>{{ $invitation->invitation_code }}</code>
                                    <div class="data-meta text-break mt-1">{{ $registerLink }}</div>
                                </td>
                                <td>
                                    <span class="badge {{ $status['class'] }}">{{ $status['label'] }}</span>
                                </td>
                                <td>{{ $invitation->expired_at?->format('d M Y H:i') ?? '-' }}</td>
                                <td>{{ $invitation->creator?->name ?? '-' }}</td>
                                <td class="text-end pe-4">
                                    <div class="table-actions">
                                        <button type="button" class="btn btn-sm btn-light-primary js-copy-link" data-link="{{ $registerLink }}">
                                            <i class="ti ti-copy"></i>
                                            Copy
                                        </button>

                                        @if ($invitation->isUnused())
                                            <form action="{{ route('invitations.revoke', $invitation) }}" method="POST" onsubmit="return confirm('Batalkan undangan ini?')">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-light-danger btn-icon" title="Batalkan">
                                                    <i class="ti ti-ban"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <div class="avtar avtar-l bg-light-secondary text-secondary">
                                            <i class="ti ti-mail-off f-28"></i>
                                        </div>
                                        <h5 class="mb-1">{{ $hasActiveFilters ? 'Tidak ada undangan yang sesuai dengan filter.' : 'Belum ada undangan registrasi.' }}</h5>
                                        <p class="text-muted mb-3">{{ $hasActiveFilters ? 'Ubah atau reset filter untuk melihat undangan lainnya.' : 'Buat undangan dari halaman Data Pegawai untuk pegawai yang belum punya akun.' }}</p>
                                        <a href="{{ $hasActiveFilters ? route('invitations.index') : route('employees.index') }}" class="btn btn-primary">
                                            <i class="ti {{ $hasActiveFilters ? 'ti-filter-off' : 'ti-users' }}"></i>
                                            {{ $hasActiveFilters ? 'Reset Filter' : 'Buka Data Pegawai' }}
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($invitations->hasPages())
            <div class="card-footer">
                {{ $invitations->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('.js-copy-link').forEach((button) => {
            button.addEventListener('click', async () => {
                await navigator.clipboard.writeText(button.dataset.link);
                button.innerHTML = '<i class="ti ti-check"></i> Copied';
            });
        });
    </script>
@endpush
