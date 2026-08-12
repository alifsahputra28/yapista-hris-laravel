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
        $scanMethods = ['qr' => 'QR Code', 'manual' => 'Manual', 'barcode' => 'Barcode (Histori)'];
        $activeInstitution = $institutions->firstWhere('id', (int) request('institution_id'))?->name;
        $activePosition = $positions->firstWhere('id', (int) request('position_id'))?->name;
        $advancedFilterCount = collect(['position_id', 'scan_method'])->filter(fn ($key) => request()->filled($key))->count();
        $hasActiveFilters = collect(['search', 'institution_id', 'position_id', 'attendance_status', 'scan_method'])->contains(fn ($key) => request()->filled($key));
        $activeFilterCount = collect(['institution_id', 'position_id', 'attendance_status', 'scan_method'])->filter(fn ($key) => request()->filled($key))->count();
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
            <form id="attendance-filter-form" method="GET" action="{{ route('events.attendances.index', $event) }}" class="row g-3 align-items-end">
                <div class="col-lg-5">
                    <label for="search" class="form-label">Cari Peserta</label>
                    <div class="filter-search-wrap"><i class="ti ti-search" aria-hidden="true"></i><input id="search" type="search" name="search" value="{{ $search }}" class="form-control" placeholder="Cari nama, NUP, email, atau HP..." aria-label="Cari peserta pada daftar hadir"></div>
                </div>
                <div class="col-12 d-lg-none"><button class="btn btn-light-primary w-100" type="button" data-bs-toggle="collapse" data-bs-target=".attendance-mobile-filter" aria-expanded="{{ $activeFilterCount ? 'true' : 'false' }}"><i class="ti ti-adjustments-horizontal" aria-hidden="true"></i> Filter @if ($activeFilterCount)({{ $activeFilterCount }})@endif</button></div>
                <div class="col-md-6 col-lg-3 collapse d-lg-block attendance-mobile-filter {{ $activeFilterCount ? 'show' : '' }}">
                    <label for="institution_id" class="form-label">Unit Kerja</label>
                    <select id="institution_id" name="institution_id" class="form-select">
                        <option value="">Semua unit</option>
                        @foreach ($institutions as $institution)
                            <option value="{{ $institution->id }}" @selected((string) request('institution_id') === (string) $institution->id)>{{ $institution->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-lg-3 collapse d-lg-block attendance-mobile-filter {{ $activeFilterCount ? 'show' : '' }}">
                    <label for="attendance_status" class="form-label">Status Hadir</label>
                    <select id="attendance_status" name="attendance_status" class="form-select">
                        <option value="">Semua status</option>
                        <option value="hadir" @selected($attendanceStatus === 'hadir')>Hadir</option>
                        <option value="belum_hadir" @selected($attendanceStatus === 'belum_hadir')>Belum Hadir</option>
                    </select>
                </div>
                <div class="col-lg-1 filter-primary-actions collapse d-lg-block attendance-mobile-filter {{ $activeFilterCount ? 'show' : '' }}"><button type="submit" class="btn btn-primary w-100" title="Terapkan Filter"><i class="ti ti-filter" aria-hidden="true"></i><span class="d-lg-none">Terapkan Filter</span></button></div>
            </form>
            <div class="filter-secondary-row collapse d-lg-flex attendance-mobile-filter {{ $activeFilterCount ? 'show' : '' }}">
                <button class="btn btn-link filter-advanced-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#attendance-advanced-filter" aria-expanded="{{ $advancedFilterCount ? 'true' : 'false' }}" aria-controls="attendance-advanced-filter"><i class="ti ti-adjustments-horizontal" aria-hidden="true"></i>Filter Lanjutan @if ($advancedFilterCount)<span class="badge bg-light-primary text-primary">{{ $advancedFilterCount }}</span>@endif<i class="ti ti-chevron-down" aria-hidden="true"></i></button>
                @if ($hasActiveFilters)<a href="{{ route('events.attendances.index', $event) }}" class="btn btn-sm btn-link text-muted">Reset semua</a>@endif
            </div>
            <div id="attendance-advanced-filter" class="collapse {{ $advancedFilterCount ? 'show' : '' }}"><div class="filter-advanced-panel"><div class="row g-3">
                <div class="col-md-6"><label for="position_id" class="form-label">Jabatan</label><select id="position_id" name="position_id" class="form-select" form="attendance-filter-form"><option value="">Semua jabatan</option>@foreach ($positions as $position)<option value="{{ $position->id }}" @selected((string) request('position_id') === (string) $position->id)>{{ $position->name }}</option>@endforeach</select></div>
                <div class="col-md-6"><label for="scan_method" class="form-label">Metode Kehadiran</label><select id="scan_method" name="scan_method" class="form-select" form="attendance-filter-form"><option value="">Semua metode</option>@foreach ($scanMethods as $value => $label)<option value="{{ $value }}" @selected(request('scan_method') === $value)>{{ $label }}</option>@endforeach</select></div>
            </div></div></div>
            @if ($hasActiveFilters)<div class="active-filter-summary" aria-label="Filter aktif"><span class="active-filter-label">Filter aktif:</span>
                @if ($activeInstitution)<x-active-filter-chip label="Unit" :value="$activeInstitution" :url="route('events.attendances.index', array_merge(['event' => $event->id], request()->except('institution_id', 'page')))" />@endif
                @if (request('attendance_status'))<x-active-filter-chip label="Kehadiran" :value="request('attendance_status') === 'hadir' ? 'Hadir' : 'Belum Hadir'" :url="route('events.attendances.index', array_merge(['event' => $event->id], request()->except('attendance_status', 'page')))" />@endif
                @if ($activePosition)<x-active-filter-chip label="Jabatan" :value="$activePosition" :url="route('events.attendances.index', array_merge(['event' => $event->id], request()->except('position_id', 'page')))" />@endif
                @if (request('scan_method'))<x-active-filter-chip label="Metode" :value="$scanMethods[request('scan_method')] ?? request('scan_method')" :url="route('events.attendances.index', array_merge(['event' => $event->id], request()->except('scan_method', 'page')))" />@endif
            </div>@endif
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
                                        <h5 class="mb-1">{{ $hasActiveFilters ? 'Tidak ada peserta yang sesuai dengan filter.' : 'Belum ada peserta.' }}</h5>
                                        <p class="text-muted {{ $hasActiveFilters ? 'mb-3' : 'mb-0' }}">{{ $hasActiveFilters ? 'Ubah atau reset filter untuk melihat peserta lainnya.' : 'Daftar hadir akan muncul setelah peserta kegiatan dibuat.' }}</p>
                                        @if ($hasActiveFilters)<a href="{{ route('events.attendances.index', $event) }}" class="btn btn-light-primary"><i class="ti ti-filter-off"></i> Reset Filter</a>@endif
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
