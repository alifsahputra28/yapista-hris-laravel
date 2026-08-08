@extends('layouts.admin')

@section('title', 'Laporan Kehadiran Kegiatan')

@section('content')
@php
    $eventStatusBadges = [
        'draft' => ['label' => 'Draft', 'class' => 'bg-light-secondary text-secondary'],
        'active' => ['label' => 'Aktif', 'class' => 'bg-light-success text-success'],
        'closed' => ['label' => 'Ditutup', 'class' => 'bg-light-primary text-primary'],
        'cancelled' => ['label' => 'Dibatalkan', 'class' => 'bg-light-danger text-danger'],
    ];
    $eventStatus = $eventStatusBadges[$event->status] ?? ['label' => $event->status ?: '-', 'class' => 'bg-light-secondary text-secondary'];
@endphp

<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <h4 class="mb-1">Laporan Kehadiran Kegiatan</h4>
        <p class="text-muted mb-0">Detail peserta hadir dan belum hadir untuk kegiatan terpilih.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('reports.events') }}" class="btn btn-light">
            <i class="ti ti-arrow-left me-1"></i> Kembali
        </a>
        <a href="{{ route('reports.events.attendances.export', array_merge(request()->query(), ['event' => $event->id])) }}" class="btn btn-primary">
            <i class="ti ti-file-spreadsheet me-1"></i> Export Excel
        </a>
    </div>
</div>

<div class="card page-intro-card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <h5 class="mb-1">{{ $event->name }}</h5>
                <div class="text-muted">
                    {{ $event->event_date?->format('d M Y') ?: '-' }}
                    @if ($event->start_time)
                        | {{ $event->start_time->format('H:i') }}
                        @if ($event->end_time)
                            - {{ $event->end_time->format('H:i') }}
                        @endif
                    @endif
                    @if ($event->location)
                        | {{ $event->location }}
                    @endif
                </div>
            </div>
            <span class="badge {{ $eventStatus['class'] }}">{{ $eventStatus['label'] }}</span>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="card summary-card mb-0">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="avtar bg-light-primary text-primary"><i class="ti ti-users"></i></div>
                <div>
                    <div class="text-muted small">Total Peserta</div>
                    <h5 class="mb-0">{{ number_format($totalParticipants) }}</h5>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card summary-card mb-0">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="avtar bg-light-success text-success"><i class="ti ti-user-check"></i></div>
                <div>
                    <div class="text-muted small">Hadir</div>
                    <h5 class="mb-0">{{ number_format($attendedCount) }}</h5>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card summary-card mb-0">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="avtar bg-light-warning text-warning"><i class="ti ti-user-question"></i></div>
                <div>
                    <div class="text-muted small">Belum Hadir</div>
                    <h5 class="mb-0">{{ number_format($absentCount) }}</h5>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card summary-card mb-0">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="avtar bg-light-info text-info"><i class="ti ti-chart-pie"></i></div>
                <div>
                    <div class="text-muted small">Persentase Hadir</div>
                    <h5 class="mb-0">{{ $attendancePercentage }}%</h5>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card filter-card mb-4">
    <div class="card-body">
        <form action="{{ route('reports.events.attendances', $event) }}" method="GET">
            <div class="row g-3">
                <div class="col-lg-4">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" name="search" id="search" class="form-control" value="{{ request('search') }}" placeholder="Nama atau NUP / nomor pegawai">
                </div>
                <div class="col-md-6 col-lg-3">
                    <label for="institution_id" class="form-label">Unit Kerja</label>
                    <select name="institution_id" id="institution_id" class="form-select">
                        <option value="">Semua unit</option>
                        @foreach ($institutions as $institution)
                            <option value="{{ $institution->id }}" @selected((string) request('institution_id') === (string) $institution->id)>
                                {{ $institution->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-lg-3">
                    <label for="position_id" class="form-label">Jabatan</label>
                    <select name="position_id" id="position_id" class="form-select">
                        <option value="">Semua jabatan</option>
                        @foreach ($positions as $position)
                            <option value="{{ $position->id }}" @selected((string) request('position_id') === (string) $position->id)>
                                {{ $position->name }}
                                @if ($position->institution)
                                    - {{ $position->institution->name }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-lg-2">
                    <label for="attendance_status" class="form-label">Status Hadir</label>
                    <select name="attendance_status" id="attendance_status" class="form-select">
                        <option value="">Semua</option>
                        <option value="present" @selected(request('attendance_status') === 'present')>Hadir</option>
                        <option value="absent" @selected(request('attendance_status') === 'absent')>Belum Hadir</option>
                    </select>
                </div>
                <div class="col-md-6 col-lg-2">
                    <label for="scan_method" class="form-label">Metode</label>
                    <select name="scan_method" id="scan_method" class="form-select">
                        <option value="">Semua</option>
                        <option value="qr" @selected(request('scan_method') === 'qr')>QR Code</option>
                        <option value="manual" @selected(request('scan_method') === 'manual')>Manual</option>
                        <option value="barcode" @selected(request('scan_method') === 'barcode')>Barcode (Histori)</option>
                    </select>
                </div>
                <div class="col-md-6 col-lg-10 d-flex align-items-end justify-content-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-filter me-1"></i> Filter
                    </button>
                    <a href="{{ route('reports.events.attendances', $event) }}" class="btn btn-light">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        @if ($participants->count())
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 70px;">No</th>
                            <th>Pegawai</th>
                            <th>Unit & Jabatan</th>
                            <th>Status Hadir</th>
                            <th>Waktu Scan</th>
                            <th>Metode</th>
                            <th>Petugas Scan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($participants as $participant)
                            @php
                                $employee = $participant->employee;
                                $attendance = $employee ? $attendanceMap->get($employee->id) : null;
                            @endphp
                            <tr>
                                <td>{{ $participants->firstItem() + $loop->index }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $employee?->full_name ?: '-' }}</div>
                                    <div class="data-meta">NUP / Nomor Pegawai: {{ $employee?->employee_number ?: 'Belum dibuat' }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $employee?->institution?->name ?: '-' }}</div>
                                    <div class="data-meta">{{ $employee?->position?->name ?: '-' }}</div>
                                </td>
                                <td>
                                    @if ($attendance)
                                        <span class="badge bg-light-success text-success">Hadir</span>
                                    @else
                                        <span class="badge bg-light-secondary text-secondary">Belum Hadir</span>
                                    @endif
                                </td>
                                <td>{{ $attendance?->scanned_at?->format('d M Y H:i:s') ?: '-' }}</td>
                                <td>
                                    @if ($attendance)
                                        <span class="badge {{ $attendance->scan_method === 'manual' ? 'bg-light-warning text-warning' : 'bg-light-primary text-primary' }}">
                                            {{ $attendance->scan_method_label }}
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $attendance?->scanner?->name ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">
                <div class="avtar bg-light-secondary text-secondary"><i class="ti ti-clipboard-off"></i></div>
                <h6 class="mb-1">Belum ada data peserta</h6>
                <p class="text-muted mb-0">Ubah filter atau pastikan peserta kegiatan sudah dibuat.</p>
            </div>
        @endif
    </div>
    @if ($participants->hasPages())
        <div class="card-footer">
            {{ $participants->links() }}
        </div>
    @endif
</div>
@endsection
