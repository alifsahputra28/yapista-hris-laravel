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

    <div class="page-header">
        <div class="page-block">
            <div class="row align-items-center">
                <div class="col-md-12">
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route($dashboardRoute) }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ $eventBackRoute }}">Kegiatan</a></li>
                        <li class="breadcrumb-item" aria-current="page">Daftar Hadir</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

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

    <div class="card page-intro-card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <h4 class="mb-1">Daftar Hadir Kegiatan</h4>
                    <p class="mb-0 text-muted">Pantau peserta yang sudah hadir dan belum hadir pada kegiatan ini.</p>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    @if ($event->canScanAttendance())
                        <a href="{{ route('events.scanner', $event) }}" class="btn btn-primary">
                            <i class="ti ti-barcode"></i>
                            Scan Barcode
                        </a>
                    @endif
                    <a href="{{ $eventBackRoute }}" class="btn btn-light-secondary">Kembali</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Informasi Kegiatan</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <small class="text-muted d-block">Nama Kegiatan</small>
                            <strong>{{ $event->name }}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Status</small>
                            <span class="badge {{ $eventStatusClasses[$event->status] ?? 'bg-light-secondary text-secondary' }}">
                                {{ $event->status_label }}
                            </span>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Tanggal</small>
                            {{ $event->event_date?->format('d M Y') }}
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Waktu</small>
                            {{ $event->start_time?->format('H:i') ?? '-' }}
                            @if ($event->end_time)
                                - {{ $event->end_time->format('H:i') }}
                            @endif
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">Lokasi</small>
                            {{ $event->location ?? '-' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Persentase Hadir</h5>
                </div>
                <div class="card-body">
                    <h3 class="mb-2">{{ $attendancePercentage }}%</h3>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $attendancePercentage }}%;" aria-valuenow="{{ $attendancePercentage }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <p class="text-muted mb-0 mt-2">{{ $attendedCount }} dari {{ $totalParticipants }} peserta sudah hadir.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 col-xl-3">
            <div class="card summary-card">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avtar avtar-s bg-light-primary text-primary"><i class="ti ti-users f-20"></i></div>
                        <div>
                            <div class="text-muted small">Total Peserta</div>
                            <h4 class="mb-0">{{ number_format($totalParticipants) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card summary-card">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avtar avtar-s bg-light-success text-success"><i class="ti ti-user-check f-20"></i></div>
                        <div>
                            <div class="text-muted small">Sudah Hadir</div>
                            <h4 class="mb-0">{{ number_format($attendedCount) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card summary-card">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avtar avtar-s bg-light-warning text-warning"><i class="ti ti-user-exclamation f-20"></i></div>
                        <div>
                            <div class="text-muted small">Belum Hadir</div>
                            <h4 class="mb-0">{{ number_format($absentCount) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card summary-card">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avtar avtar-s bg-light-info text-info"><i class="ti ti-chart-pie f-20"></i></div>
                        <div>
                            <div class="text-muted small">Persentase</div>
                            <h4 class="mb-0">{{ $attendancePercentage }}%</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
                        <input id="note" type="text" name="note" class="form-control" placeholder="Contoh: barcode rusak atau scanner bermasalah">
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
