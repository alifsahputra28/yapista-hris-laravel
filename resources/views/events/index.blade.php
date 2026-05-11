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

    <div class="page-header">
        <div class="page-block">
            <div class="row align-items-center">
                <div class="col-md-12">
                    <div class="page-header-title">
                        <h5 class="m-b-10">Kegiatan Yayasan</h5>
                    </div>

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

    <div class="card">
        <div class="card-header">
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <h5 class="mb-0">Daftar Kegiatan</h5>

                <a href="{{ route('events.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus"></i>
                    Tambah Kegiatan
                </a>
            </div>
        </div>

        <div class="card-body">
            <form method="GET" action="{{ route('events.index') }}" class="row g-2 mb-3">
                <div class="col-md-3">
                    <input
                        type="search"
                        name="search"
                        value="{{ $search }}"
                        class="form-control"
                        placeholder="Cari nama atau lokasi"
                    >
                </div>

                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">Semua status</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <select name="target_type" class="form-select">
                        <option value="">Semua target</option>
                        @foreach ($targetTypes as $value => $label)
                            <option value="{{ $value }}" @selected(request('target_type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
                </div>

                <div class="col-md-2">
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
                </div>

                <div class="col-md-1 d-grid">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="ti ti-filter"></i>
                    </button>
                </div>

                <div class="col-12">
                    <a href="{{ route('events.index') }}" class="btn btn-light-secondary">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover table-borderless mb-0">
                    <thead>
                        <tr>
                            <th style="width: 70px;">No</th>
                            <th>Nama Kegiatan</th>
                            <th>Tanggal</th>
                            <th>Waktu</th>
                            <th>Lokasi</th>
                            <th>Target</th>
                            <th>Status</th>
                            <th>Jumlah Peserta</th>
                            <th>Dibuat Oleh</th>
                            <th class="text-end" style="width: 240px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($events as $event)
                            <tr>
                                <td>{{ $events->firstItem() + $loop->index }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $event->name }}</div>
                                </td>
                                <td>{{ $event->event_date?->format('d M Y') }}</td>
                                <td>
                                    {{ $event->start_time?->format('H:i') ?? '-' }}
                                    @if ($event->end_time)
                                        - {{ $event->end_time->format('H:i') }}
                                    @endif
                                </td>
                                <td>{{ $event->location ?? '-' }}</td>
                                <td>{{ $event->target_type_label }}</td>
                                <td>
                                    <span class="badge {{ $statusClasses[$event->status] ?? 'bg-light-secondary text-secondary' }}">
                                        {{ $event->status_label }}
                                    </span>
                                </td>
                                <td>{{ $event->participants_count }}</td>
                                <td>{{ $event->creator?->name ?? '-' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('events.show', $event) }}" class="btn btn-sm btn-light-secondary">
                                        <i class="ti ti-eye"></i>
                                        Detail
                                    </a>

                                    @if ($event->canBeEdited())
                                        <a href="{{ route('events.edit', $event) }}" class="btn btn-sm btn-light-primary">
                                            <i class="ti ti-edit"></i>
                                            Edit
                                        </a>
                                    @endif

                                    @if ($event->isDraft() || $event->isCancelled())
                                        <form action="{{ route('events.destroy', $event) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus kegiatan ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light-danger">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted">Belum ada kegiatan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $events->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
@endsection
