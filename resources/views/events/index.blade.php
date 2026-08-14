@extends('layouts.admin')

@section('title', 'Kegiatan Yayasan | YAPISTA HRIS')

@section('content')
    @php
        $targetTypes = \App\Models\Event::TARGET_TYPES;
        $statuses = \App\Models\Event::STATUSES;
        $statusClasses = [
            'draft' => 'bg-light-secondary text-secondary',
            'active' => 'bg-light-success text-success',
            'closed' => 'bg-light-primary text-primary',
            'cancelled' => 'bg-light-danger text-danger',
        ];
        $advancedFilterCount = request()->filled('target_type') ? 1 : 0;
        $hasActiveFilters = collect(['search', 'status', 'target_type', 'date_from', 'date_to'])->contains(fn ($key) => request()->filled($key));
        $activeFilterCount = collect(['status', 'target_type', 'date_from', 'date_to'])->filter(fn ($key) => request()->filled($key))->count();
    @endphp

    <x-page-header
        title="Kegiatan Yayasan"
        subtitle="Kelola jadwal, target peserta, dan kesiapan absensi QR Code."
        :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Kegiatan']]"
    >
        <x-slot:actions>
            <a href="{{ route('events.create') }}" class="btn btn-primary">
                <i class="ti ti-plus"></i> Tambah Kegiatan
            </a>
        </x-slot:actions>
    </x-page-header>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="metric-strip mb-4" aria-label="Ringkasan kegiatan">
        <div class="metric-item"><div class="metric-label">Total</div><div class="metric-value">{{ number_format($totalEvents ?? 0) }}</div></div>
        <div class="metric-item"><div class="metric-label">Draft</div><div class="metric-value">{{ number_format($draftEvents ?? 0) }}</div></div>
        <div class="metric-item"><div class="metric-label">Aktif</div><div class="metric-value">{{ number_format($activeEvents ?? 0) }}</div></div>
        <div class="metric-item"><div class="metric-label">Ditutup</div><div class="metric-value">{{ number_format($closedEvents ?? 0) }}</div></div>
    </div>

    <div class="card filter-card">
        <div class="card-header">
            <h5 class="mb-0">Filter Kegiatan</h5>
        </div>
        <div class="card-body">
            <form id="event-filter-form" method="GET" action="{{ route('events.index') }}" class="row g-3 align-items-end">
                <div class="col-lg-5">
                    <label for="search" class="form-label">Cari Kegiatan</label>
                    <div class="filter-search-wrap"><i class="ti ti-search" aria-hidden="true"></i><input id="search" type="search" name="search" value="{{ $search }}" class="form-control" placeholder="Cari nama kegiatan atau lokasi..." aria-label="Cari kegiatan"></div>
                </div>
                <div class="col-12 d-lg-none"><button class="btn btn-light-primary w-100" type="button" data-bs-toggle="collapse" data-bs-target=".event-mobile-filter" aria-expanded="{{ $activeFilterCount ? 'true' : 'false' }}"><i class="ti ti-adjustments-horizontal" aria-hidden="true"></i> Filter @if ($activeFilterCount)({{ $activeFilterCount }})@endif</button></div>
                <div class="col-md-6 col-lg-2 collapse d-lg-block event-mobile-filter {{ $activeFilterCount ? 'show' : '' }}">
                    <label for="status" class="form-label">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">Semua status</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 col-lg-2 collapse d-lg-block event-mobile-filter {{ $activeFilterCount ? 'show' : '' }}">
                    <label for="date_from" class="form-label">Dari Tanggal</label>
                    <input id="date_from" type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
                </div>

                <div class="col-md-6 col-lg-2 collapse d-lg-block event-mobile-filter {{ $activeFilterCount ? 'show' : '' }}">
                    <label for="date_to" class="form-label">Sampai Tanggal</label>
                    <input id="date_to" type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
                </div>

                <div class="col-lg-1 filter-primary-actions collapse d-lg-block event-mobile-filter {{ $activeFilterCount ? 'show' : '' }}"><button type="submit" class="btn btn-primary w-100" title="Terapkan Filter"><i class="ti ti-filter" aria-hidden="true"></i><span class="d-lg-none">Terapkan Filter</span></button></div>
            </form>
            <div class="filter-secondary-row collapse d-lg-flex event-mobile-filter {{ $activeFilterCount ? 'show' : '' }}">
                <button class="btn btn-link filter-advanced-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#event-advanced-filter" aria-expanded="{{ $advancedFilterCount ? 'true' : 'false' }}" aria-controls="event-advanced-filter"><i class="ti ti-adjustments-horizontal" aria-hidden="true"></i>Filter Lanjutan @if ($advancedFilterCount)<span class="badge bg-light-primary text-primary">{{ $advancedFilterCount }}</span>@endif<i class="ti ti-chevron-down" aria-hidden="true"></i></button>
                @if ($hasActiveFilters)<a href="{{ route('events.index') }}" class="btn btn-sm btn-link text-muted">Reset semua</a>@endif
            </div>
            <div id="event-advanced-filter" class="collapse {{ $advancedFilterCount ? 'show' : '' }}"><div class="filter-advanced-panel"><div class="row g-3"><div class="col-md-6 col-lg-4"><label for="target_type" class="form-label">Target Peserta</label><select id="target_type" name="target_type" class="form-select" form="event-filter-form"><option value="">Semua target</option>@foreach ($targetTypes as $value => $label)<option value="{{ $value }}" @selected(request('target_type') === $value)>{{ $label }}</option>@endforeach</select></div></div></div></div>
            @if ($hasActiveFilters)
                <div class="active-filter-summary" aria-label="Filter aktif"><span class="active-filter-label">Filter aktif:</span>
                    @if (request('status'))<x-active-filter-chip label="Status" :value="$statuses[request('status')] ?? request('status')" :url="route('events.index', request()->except('status', 'page'))" />@endif
                    @if (request('date_from'))<x-active-filter-chip label="Mulai" :value="request('date_from')" :url="route('events.index', request()->except('date_from', 'page'))" />@endif
                    @if (request('date_to'))<x-active-filter-chip label="Sampai" :value="request('date_to')" :url="route('events.index', request()->except('date_to', 'page'))" />@endif
                    @if (request('target_type'))<x-active-filter-chip label="Target" :value="$targetTypes[request('target_type')] ?? request('target_type')" :url="route('events.index', request()->except('target_type', 'page'))" />@endif
                </div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <h5 class="mb-0">Daftar Kegiatan</h5>
                <span class="text-muted small">{{ $events->total() }} data berdasarkan filter saat ini</span>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4" style="width: 70px;">No</th>
                            <th>Kegiatan</th>
                            <th>Jadwal</th>
                            <th>Target</th>
                            <th>Status</th>
                            <th>Peserta</th>
                            <th>Dibuat Oleh</th>
                            <th class="text-end pe-4" style="width: 170px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($events as $event)
                            <tr>
                                <td class="ps-4">{{ $events->firstItem() + $loop->index }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $event->name }}</div>
                                    <div class="data-meta">{{ $event->location ?? 'Lokasi belum diisi' }}</div>
                                </td>
                                <td>
                                    <div>{{ $event->event_date?->locale('id')->translatedFormat('d M Y') }}</div>
                                    <div class="data-meta">
                                        {{ $event->start_time?->format('H:i') ?? '-' }}
                                        @if ($event->end_time)
                                            - {{ $event->end_time->format('H:i') }}
                                        @endif
                                    </div>
                                </td>
                                <td>{{ $event->target_type_label }}</td>
                                <td>
                                    <span class="badge {{ $statusClasses[$event->status] ?? 'bg-light-secondary text-secondary' }}">
                                        {{ $event->status_label }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-light-info text-info">{{ $event->participants_count }} peserta</span>
                                </td>
                                <td>{{ $event->creator?->name ?? '-' }}</td>
                                <td class="text-end pe-4">
                                    <div class="table-actions">
                                        <a href="{{ route('events.show', $event) }}" class="btn btn-sm btn-light-secondary">
                                            <i class="ti ti-eye"></i>
                                            Detail
                                        </a>

                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-light-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                Aksi
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <a href="{{ route('events.attendances.index', $event) }}" class="dropdown-item">
                                                    <i class="ti ti-list-check"></i>
                                                    Daftar Hadir
                                                </a>

                                                @if ($event->canScanAttendance())
                                                    <a href="{{ route('events.scanner', $event) }}" class="dropdown-item">
                                                        <i class="ti ti-qrcode"></i>
                                                        Scan QR Code
                                                    </a>
                                                @endif

                                                @if ($event->canBeEdited())
                                                    <a href="{{ route('events.edit', $event) }}" class="dropdown-item">
                                                        <i class="ti ti-edit"></i>
                                                        Edit
                                                    </a>
                                                @endif

                                                @if ($event->isDraft() || $event->isCancelled())
                                                    <div class="dropdown-divider"></div>
                                                    <form action="{{ route('events.destroy', $event) }}" method="POST" data-confirm-title="Hapus Kegiatan?" data-confirm-message="Kegiatan akan dihapus jika belum memiliki data yang menghalangi penghapusan. Lanjutkan?">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger">
                                                            <i class="ti ti-trash"></i>
                                                            Hapus
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state">
                                        <div class="avtar avtar-l bg-light-secondary text-secondary">
                                            <i class="ti ti-calendar-off f-28"></i>
                                        </div>
                                        <h5 class="mb-1">{{ $hasActiveFilters ? 'Tidak ada kegiatan yang sesuai dengan filter.' : 'Belum ada kegiatan.' }}</h5>
                                        <p class="text-muted mb-3">{{ $hasActiveFilters ? 'Ubah atau reset filter untuk melihat kegiatan lainnya.' : 'Silakan tambahkan kegiatan yayasan terlebih dahulu.' }}</p>
                                        <a href="{{ $hasActiveFilters ? route('events.index') : route('events.create') }}" class="btn btn-primary">
                                            <i class="ti {{ $hasActiveFilters ? 'ti-filter-off' : 'ti-plus' }}"></i>
                                            {{ $hasActiveFilters ? 'Reset Filter' : 'Tambah Kegiatan' }}
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($events->hasPages())
            <div class="card-footer">
                {{ $events->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
@endsection
