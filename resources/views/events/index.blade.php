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
        $summaryCards = [
            ['label' => 'Total Kegiatan', 'value' => $totalEvents ?? 0, 'icon' => 'ti-calendar-event', 'class' => 'bg-light-primary text-primary'],
            ['label' => 'Draft', 'value' => $draftEvents ?? 0, 'icon' => 'ti-file-pencil', 'class' => 'bg-light-secondary text-secondary'],
            ['label' => 'Aktif', 'value' => $activeEvents ?? 0, 'icon' => 'ti-player-play', 'class' => 'bg-light-success text-success'],
            ['label' => 'Ditutup', 'value' => $closedEvents ?? 0, 'icon' => 'ti-lock', 'class' => 'bg-light-info text-info'],
        ];
    @endphp

    <div class="page-header">
        <div class="page-block">
            <div class="row align-items-center">
                <div class="col-md-12">
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item" aria-current="page">Kegiatan</li>
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

    <div class="card page-intro-card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <h4 class="mb-1">Kegiatan Yayasan</h4>
                    <p class="mb-0 text-muted">Kelola kegiatan, target peserta, dan status kegiatan sebelum absensi QR digunakan.</p>
                </div>

                <a href="{{ route('events.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus"></i>
                    Tambah Kegiatan
                </a>
            </div>
        </div>
    </div>

    <div class="row">
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
                                                @if ($event->canBeEdited())
                                                    <a href="{{ route('events.edit', $event) }}" class="dropdown-item">
                                                        <i class="ti ti-edit"></i>
                                                        Edit
                                                    </a>
                                                @endif

                                                @if ($event->isDraft() || $event->isCancelled())
                                                    <form action="{{ route('events.destroy', $event) }}" method="POST" onsubmit="return confirm('Hapus kegiatan ini?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger">
                                                            <i class="ti ti-trash"></i>
                                                            Hapus
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="dropdown-item text-muted">Tidak ada aksi tambahan</span>
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
