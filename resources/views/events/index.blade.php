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
            <form method="GET" action="{{ route('events.index') }}" class="row g-3">
                <div class="col-lg-4">
                    <label for="search" class="form-label">Pencarian</label>
                    <input id="search" type="search" name="search" value="{{ $search }}" class="form-control" placeholder="Cari nama kegiatan atau lokasi">
                </div>

                <div class="col-md-6 col-lg-2">
                    <label for="status" class="form-label">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">Semua status</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 col-lg-2">
                    <label for="target_type" class="form-label">Target</label>
                    <select id="target_type" name="target_type" class="form-select">
                        <option value="">Semua target</option>
                        @foreach ($targetTypes as $value => $label)
                            <option value="{{ $value }}" @selected(request('target_type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 col-lg-2">
                    <label for="date_from" class="form-label">Dari Tanggal</label>
                    <input id="date_from" type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
                </div>

                <div class="col-md-6 col-lg-2">
                    <label for="date_to" class="form-label">Sampai Tanggal</label>
                    <input id="date_to" type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
                </div>

                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-filter"></i>
                        Filter
                    </button>
                    <a href="{{ route('events.index') }}" class="btn btn-light-secondary">Reset</a>
                </div>
            </form>
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
                                    <div>{{ $event->event_date?->format('d M Y') }}</div>
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
                                                    <form action="{{ route('events.destroy', $event) }}" method="POST" onsubmit="return confirm('Hapus kegiatan ini?')">
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
                                        <h5 class="mb-1">Belum ada kegiatan.</h5>
                                        <p class="text-muted mb-3">Silakan tambahkan kegiatan yayasan terlebih dahulu.</p>
                                        <a href="{{ route('events.create') }}" class="btn btn-primary">
                                            <i class="ti ti-plus"></i>
                                            Tambah Kegiatan
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
