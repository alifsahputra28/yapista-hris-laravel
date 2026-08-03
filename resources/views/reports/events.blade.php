@extends('layouts.admin')

@section('title', 'Laporan Kegiatan')

@section('content')
@php
    $statusBadges = [
        'draft' => ['label' => 'Draft', 'class' => 'bg-light-secondary text-secondary'],
        'active' => ['label' => 'Aktif', 'class' => 'bg-light-success text-success'],
        'closed' => ['label' => 'Ditutup', 'class' => 'bg-light-primary text-primary'],
        'cancelled' => ['label' => 'Dibatalkan', 'class' => 'bg-light-danger text-danger'],
    ];
@endphp

<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <h4 class="mb-1">Laporan Kegiatan</h4>
        <p class="text-muted mb-0">Rekap kegiatan yayasan, peserta, dan persentase kehadiran.</p>
    </div>
    <a href="{{ route('reports.events.export', request()->query()) }}" class="btn btn-primary">
        <i class="ti ti-file-spreadsheet me-1"></i> Export Excel
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="card summary-card mb-0">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="avtar bg-light-primary text-primary"><i class="ti ti-calendar-event"></i></div>
                <div>
                    <div class="text-muted small">Total Kegiatan</div>
                    <h5 class="mb-0">{{ number_format($totalEvents) }}</h5>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card summary-card mb-0">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="avtar bg-light-success text-success"><i class="ti ti-activity"></i></div>
                <div>
                    <div class="text-muted small">Kegiatan Aktif</div>
                    <h5 class="mb-0">{{ number_format($activeEvents) }}</h5>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card summary-card mb-0">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="avtar bg-light-info text-info"><i class="ti ti-circle-check"></i></div>
                <div>
                    <div class="text-muted small">Kegiatan Ditutup</div>
                    <h5 class="mb-0">{{ number_format($closedEvents) }}</h5>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card summary-card mb-0">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="avtar bg-light-warning text-warning"><i class="ti ti-chart-bar"></i></div>
                <div>
                    <div class="text-muted small">Rata-rata Kehadiran</div>
                    <h5 class="mb-0">{{ $averageAttendance }}%</h5>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card filter-card mb-4">
    <div class="card-body">
        <form action="{{ route('reports.events') }}" method="GET">
            <div class="row g-3">
                <div class="col-lg-4">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" name="search" id="search" class="form-control" value="{{ request('search') }}" placeholder="Nama kegiatan atau lokasi">
                </div>
                <div class="col-md-6 col-lg-2">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">Semua</option>
                        @foreach ($eventStatuses as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-lg-2">
                    <label for="target_type" class="form-label">Target</label>
                    <select name="target_type" id="target_type" class="form-select">
                        <option value="">Semua</option>
                        @foreach ($targetTypes as $value => $label)
                            <option value="{{ $value }}" @selected(request('target_type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-lg-2">
                    <label for="date_from" class="form-label">Dari Tanggal</label>
                    <input type="date" name="date_from" id="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-6 col-lg-2">
                    <label for="date_to" class="form-label">Sampai Tanggal</label>
                    <input type="date" name="date_to" id="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-12 d-flex justify-content-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-filter me-1"></i> Filter
                    </button>
                    <a href="{{ route('reports.events') }}" class="btn btn-light">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        @if ($events->count())
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 70px;">No</th>
                            <th>Kegiatan</th>
                            <th>Waktu & Lokasi</th>
                            <th>Target</th>
                            <th>Status</th>
                            <th>Rekap Kehadiran</th>
                            <th>Dibuat Oleh</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($events as $event)
                            @php
                                $status = $statusBadges[$event->status] ?? ['label' => $event->status ?: '-', 'class' => 'bg-light-secondary text-secondary'];
                                $totalParticipants = (int) $event->active_participants_count;
                                $totalAttended = (int) $event->active_attendances_count;
                                $totalAbsent = max($totalParticipants - $totalAttended, 0);
                                $percentage = $totalParticipants > 0 ? round(($totalAttended / $totalParticipants) * 100, 1) : 0;
                            @endphp
                            <tr>
                                <td>{{ $events->firstItem() + $loop->index }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $event->name }}</div>
                                    <div class="data-meta">{{ $event->description ? \Illuminate\Support\Str::limit($event->description, 80) : 'Tidak ada deskripsi' }}</div>
                                </td>
                                <td>
                                    <div>{{ $event->event_date?->format('d M Y') ?: '-' }}</div>
                                    <div class="data-meta">
                                        {{ $event->start_time?->format('H:i') ?: '-' }}
                                        @if ($event->end_time)
                                            - {{ $event->end_time->format('H:i') }}
                                        @endif
                                    </div>
                                    <div class="data-meta">{{ $event->location ?: '-' }}</div>
                                </td>
                                <td>{{ $event->target_type_label }}</td>
                                <td><span class="badge {{ $status['class'] }}">{{ $status['label'] }}</span></td>
                                <td>
                                    <div class="fw-semibold">{{ $percentage }}%</div>
                                    <div class="data-meta">{{ $totalAttended }} hadir / {{ $totalParticipants }} peserta</div>
                                    <div class="data-meta">{{ $totalAbsent }} belum hadir</div>
                                </td>
                                <td>{{ $event->creator?->name ?: '-' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('reports.events.attendances', $event) }}" class="btn btn-sm btn-light">
                                        <i class="ti ti-list-check me-1"></i> Detail Kehadiran
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">
                <div class="avtar bg-light-secondary text-secondary"><i class="ti ti-calendar-off"></i></div>
                <h6 class="mb-1">Belum ada data kegiatan</h6>
                <p class="text-muted mb-0">Ubah filter atau buat kegiatan terlebih dahulu.</p>
            </div>
        @endif
    </div>
    @if ($events->hasPages())
        <div class="card-footer">
            {{ $events->links() }}
        </div>
    @endif
</div>
@endsection
