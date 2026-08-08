@extends('layouts.admin')

@section('title', 'Daftar Hadir Kegiatan | YAPISTA HRIS')

@section('content')
    @php
        $eventStatusClasses = [
            'draft' => 'bg-light-secondary text-secondary',
            'active' => 'bg-light-success text-success',
            'closed' => 'bg-light-primary text-primary',
            'cancelled' => 'bg-light-danger text-danger',
        ];
        $attendanceStatusClasses = [
            'present' => 'bg-light-success text-success',
            'late' => 'bg-light-warning text-warning',
            'manual' => 'bg-light-primary text-primary',
            'invalid' => 'bg-light-danger text-danger',
        ];
        $canManageAttendance = Auth::user()?->isSuperAdmin() || Auth::user()?->isHrAdmin();
        $dashboardRoute = Auth::user()?->isPanitia() ? 'scanner.dashboard' : 'dashboard';
        $eventBackRoute = $canManageAttendance ? route('events.show', $event) : route('scanner.dashboard');
    @endphp

    <x-page-header
        title="Daftar Hadir"
        subtitle="{{ $event->name }}"
        :breadcrumbs="[
            ['label' => 'Dashboard', 'url' => route($dashboardRoute)],
            ['label' => 'Kegiatan', 'url' => $eventBackRoute],
            ['label' => 'Daftar Hadir'],
        ]"
        :badge-label="$event->status_label"
        :badge-class="$eventStatusClasses[$event->status] ?? 'bg-light-secondary text-secondary'"
    >
        <x-slot:meta>
            <span>{{ $event->event_date?->format('d M Y') }}</span><span aria-hidden="true">•</span>
            <span>{{ $event->start_time?->format('H:i') ?? '-' }}{{ $event->end_time ? ' - '.$event->end_time->format('H:i') : '' }}</span><span aria-hidden="true">•</span>
            <span>{{ $event->location ?? 'Lokasi belum diisi' }}</span>
        </x-slot:meta>
        <x-slot:actions>
            @if ($event->canScanAttendance())
                <a href="{{ route('events.scanner', $event) }}" class="btn btn-primary"><i class="ti ti-qrcode"></i> Scan QR Code</a>
            @endif
            <a href="{{ $eventBackRoute }}" class="btn btn-light-secondary">Kembali</a>
        </x-slot:actions>
    </x-page-header>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('warning'))
        <div class="alert alert-warning">{{ session('warning') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="metric-strip" aria-label="Ringkasan kehadiran">
        <div class="metric-item"><div class="metric-label">Peserta Aktif</div><div class="metric-value">{{ number_format($totalParticipants) }}</div></div>
        <div class="metric-item"><div class="metric-label">Sudah Hadir</div><div class="metric-value">{{ number_format($attendedCount) }}</div></div>
        <div class="metric-item"><div class="metric-label">Belum Hadir</div><div class="metric-value">{{ number_format($absentCount) }}</div></div>
        <div class="metric-item"><div class="metric-label">Kehadiran</div><div class="metric-value">{{ $attendancePercentage }}%</div></div>
    </div>

    @if ($event->canScanAttendance() && $manualEmployees->isNotEmpty())
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Input Manual Kehadiran</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('events.attendances.manual', $event) }}" class="row g-3">
                    @csrf
                    <div class="col-lg-5">
                        <label for="employee_id" class="form-label">Pegawai</label>
                        <select id="employee_id" name="employee_id" class="form-select" required>
                            <option value="">Pilih pegawai</option>
                            @foreach ($manualEmployees as $employee)
                                <option value="{{ $employee->id }}">
                                    {{ $employee->full_name }} - {{ $employee->employee_number }}{{ $employee->institution ? ' - '.$employee->institution->name : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-5">
                        <label for="note" class="form-label">Catatan</label>
                        <input id="note" type="text" name="note" class="form-control" placeholder="Contoh: QR Code rusak atau scanner bermasalah">
                    </div>
                    <div class="col-lg-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ti ti-user-plus"></i>
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="card filter-card">
        <div class="card-header">
            <h5 class="mb-0">Filter Daftar Hadir</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('events.attendances.index', $event) }}" class="row g-3">
                <div class="col-lg-4">
                    <label for="search" class="form-label">Pencarian</label>
                    <input id="search" type="search" name="search" value="{{ $search }}" class="form-control" placeholder="Cari nama, NUP / nomor pegawai, email, atau HP">
                </div>
                <div class="col-md-6 col-lg-2">
                    <label for="institution_id" class="form-label">Unit Kerja</label>
                    <select id="institution_id" name="institution_id" class="form-select">
                        <option value="">Semua unit</option>
                        @foreach ($institutions as $institution)
                            <option value="{{ $institution->id }}" @selected((string) request('institution_id') === (string) $institution->id)>{{ $institution->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-lg-2">
                    <label for="position_id" class="form-label">Jabatan</label>
                    <select id="position_id" name="position_id" class="form-select">
                        <option value="">Semua jabatan</option>
                        @foreach ($positions as $position)
                            <option value="{{ $position->id }}" @selected((string) request('position_id') === (string) $position->id)>{{ $position->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-lg-2">
                    <label for="attendance_status" class="form-label">Status Hadir</label>
                    <select id="attendance_status" name="attendance_status" class="form-select">
                        <option value="">Semua status</option>
                        <option value="hadir" @selected($attendanceStatus === 'hadir')>Hadir</option>
                        <option value="belum_hadir" @selected($attendanceStatus === 'belum_hadir')>Belum Hadir</option>
                    </select>
                </div>
                <div class="col-md-6 col-lg-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-filter"></i>
                    </button>
                    <a href="{{ route('events.attendances.index', $event) }}" class="btn btn-light-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <h5 class="mb-0">Peserta dan Status Kehadiran</h5>
                <span class="text-muted small">{{ $participants->total() }} data berdasarkan filter saat ini</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4" style="width: 70px;">No</th>
                            <th>Pegawai</th>
                            <th>Unit & Jabatan</th>
                            <th>Status Hadir</th>
                            <th>Waktu Scan</th>
                            <th>Metode</th>
                            <th>Scanner</th>
                            <th class="text-end pe-4" style="width: 120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($participants as $participant)
                            @php
                                $employee = $participant->employee;
                                $attendance = $employee ? $attendanceMap->get($employee->id) : null;
                                $attendanceClass = $attendance
                                    ? ($attendanceStatusClasses[$attendance->attendance_status] ?? 'bg-light-success text-success')
                                    : 'bg-light-secondary text-secondary';
                            @endphp
                            <tr>
                                <td class="ps-4">{{ $participants->firstItem() + $loop->index }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $employee?->full_name ?? '-' }}</div>
                                    <div class="data-meta">NUP / Nomor Pegawai: {{ $employee?->employee_number ?? 'Belum dibuat' }}</div>
                                </td>
                                <td>
                                    <div>{{ $employee?->institution?->name ?? '-' }}</div>
                                    <div class="data-meta">{{ $employee?->position?->name ?? '-' }}</div>
                                </td>
                                <td>
                                    @if ($attendance)
                                        <span class="badge {{ $attendanceClass }}">{{ $attendance->attendance_status_label }}</span>
                                    @else
                                        <span class="badge {{ $attendanceClass }}">Belum Hadir</span>
                                    @endif
                                </td>
                                <td>{{ $attendance?->scanned_at?->format('d M Y H:i:s') ?? '-' }}</td>
                                <td>{{ $attendance?->scan_method_label ?? '-' }}</td>
                                <td>{{ $attendance?->scanner?->name ?? '-' }}</td>
                                <td class="text-end pe-4">
                                    <div class="table-actions">
                                        @if ($attendance && $canManageAttendance && ! $event->isClosed())
                                            <form method="POST" action="{{ route('event-attendances.destroy', $attendance) }}" onsubmit="return confirm('Hapus attendance ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-light-danger btn-icon" title="Hapus">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state">
                                        <div class="avtar avtar-l bg-light-secondary text-secondary">
                                            <i class="ti ti-users-off f-28"></i>
                                        </div>
                                        <h5 class="mb-1">Belum ada peserta.</h5>
                                        <p class="text-muted mb-0">Daftar hadir akan muncul setelah peserta kegiatan dibuat.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($participants->hasPages())
            <div class="card-footer">
                {{ $participants->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
@endsection
