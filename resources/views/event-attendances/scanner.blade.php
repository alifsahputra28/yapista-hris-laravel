@extends('layouts.admin')

@section('layout-mode', 'scanner')
@section('title', 'Scan Kehadiran | YAPISTA HRIS')

@section('content')
    @php
        $exitRoute = Auth::user()?->isPanitia()
            ? route('scanner.dashboard')
            : route('events.show', $event);
        $manualHasErrors = $errors->has('employee_id') || $errors->has('note');
        $latestAttendance = $recentAttendances->first();
    @endphp

    <header class="scanner-focus-header" aria-labelledby="scanner-page-title">
        <div class="scanner-focus-brand">
            <x-application-logo class="scanner-focus-logo" image-class="img-fluid" />
            <div class="scanner-focus-heading">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <h1 id="scanner-page-title">Scan Kehadiran</h1>
                    <span class="badge bg-light-success text-success">{{ $event->status_label }}</span>
                </div>
                <strong>{{ $event->name }}</strong>
                <div class="scanner-event-meta">
                    <span><i class="ti ti-calendar" aria-hidden="true"></i>{{ $event->event_date?->locale('id')->translatedFormat('d M Y') ?? 'Tanggal belum diisi' }}</span>
                    <span><i class="ti ti-clock" aria-hidden="true"></i>{{ $event->start_time?->format('H:i') ?? '-' }}{{ $event->end_time ? ' - '.$event->end_time->format('H:i') : '' }} WIB</span>
                    <span><i class="ti ti-map-pin" aria-hidden="true"></i>{{ $event->location ?? 'Lokasi belum diisi' }}</span>
                </div>
            </div>
        </div>
        <div class="scanner-focus-actions">
            <a href="{{ route('events.attendances.index', $event) }}" class="btn btn-light-primary">
                <i class="ti ti-list" aria-hidden="true"></i> Daftar Kehadiran
            </a>
            <a href="{{ $exitRoute }}" class="btn btn-light-secondary">
                <i class="ti ti-logout" aria-hidden="true"></i> Keluar Scanner
            </a>
        </div>
    </header>

    <div class="scanner-flash-stack" aria-live="polite">
        @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if (session('warning'))<div class="alert alert-warning">{{ session('warning') }}</div>@endif
        @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
        @if ($errors->any() && ! $manualHasErrors)<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    </div>

    <div class="metric-strip scanner-metric-strip" aria-label="Ringkasan kehadiran">
        <div class="metric-item"><div class="metric-label">Total Peserta</div><div class="metric-value" data-metric="participants">{{ $totalParticipants }}</div></div>
        <div class="metric-item"><div class="metric-label">Sudah Hadir</div><div class="metric-value" data-metric="attended">{{ $attendedCount }}</div></div>
        <div class="metric-item"><div class="metric-label">Belum Hadir</div><div class="metric-value" data-metric="absent">{{ $absentCount }}</div></div>
        <div class="metric-item"><div class="metric-label">Tingkat Kehadiran</div><div class="metric-value" data-metric="percentage">{{ $attendancePercentage }}%</div></div>
    </div>

    <section class="scanner-workspace" aria-labelledby="scanner-input-heading">
        <div class="scanner-stage">
            <div class="scanner-state is-ready" data-scanner-state role="status" aria-live="polite">
                <span class="scanner-state-icon"><i class="ti ti-scan" data-state-icon aria-hidden="true"></i></span>
                <h2 id="scanner-input-heading" data-state-title>Siap menerima scanner</h2>
                <p data-state-message>Arahkan scanner ke QR Code pada ID Card pegawai.</p>
            </div>
            <form id="qr-scan-form" class="scanner-input-form" method="POST" action="{{ route('events.scan', $event) }}">
                @csrf
                <label for="qr_payload" class="visually-hidden">QR Code Pegawai</label>
                <input id="qr_payload" type="password" name="qr_payload"
                    class="form-control form-control-lg text-center" autocomplete="off"
                    maxlength="128" placeholder="Pindai QR Code pegawai..." autofocus required>
                <button type="submit" class="btn btn-light-secondary mt-3" data-scan-submit>
                    <i class="ti ti-qrcode" aria-hidden="true"></i> Proses Kehadiran
                </button>
            </form>
        </div>

        <div class="scanner-result-overlay" data-result-overlay role="status" aria-live="polite" aria-atomic="true" hidden>
            <div class="scanner-result-heading"><i class="ti ti-circle-check" data-result-icon aria-hidden="true"></i><strong data-result-title></strong></div>
            <p class="small mb-0" data-result-message></p>
            <div class="scanner-result-person" data-result-person hidden>
                <span class="scanner-result-avatar"><i class="ti ti-user" aria-hidden="true"></i></span>
                <div class="scanner-result-identity">
                    <strong data-result-name></strong>
                    <span>NUP <span data-result-number></span></span>
                    <span data-result-unit></span>
                    <span data-result-position></span>
                    <small data-result-time></small>
                </div>
            </div>
        </div>

        <div class="scanner-workspace-footer">
            <div class="scanner-last-line" data-last-scan>
                <i class="ti {{ $latestAttendance ? 'ti-circle-check text-success' : 'ti-clock' }}" data-last-icon aria-hidden="true"></i>
                <span><span class="text-muted">Terakhir:</span> <span data-last-summary>{{ $latestAttendance ? ($latestAttendance->employee?->full_name ?? 'Pegawai tidak tersedia').' - '.$latestAttendance->scan_method_label.' - '.$latestAttendance->scanned_at?->format('H:i:s').' WIB' : 'Belum ada kehadiran tercatat.' }}</span></span>
            </div>
            <div class="scanner-secondary-actions">
                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#manual-attendance-modal">
                    <i class="ti ti-user-plus" aria-hidden="true"></i> Kehadiran Manual
                </button>
                <button type="button" class="btn btn-light-secondary" data-bs-toggle="offcanvas" data-bs-target="#recent-attendance-panel" aria-controls="recent-attendance-panel">
                    <i class="ti ti-history" aria-hidden="true"></i> Kehadiran Terbaru
                </button>
            </div>
        </div>
    </section>

    <div class="modal fade scanner-manual-modal" id="manual-attendance-modal" tabindex="-1" aria-labelledby="manual-attendance-title" data-reopen="{{ $manualHasErrors ? 'true' : 'false' }}">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5" id="manual-attendance-title">Kehadiran Manual</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Gunakan jika QR Code tidak dapat dipindai atau scanner mengalami kendala.</p>
                    @if ($manualEmployees->isNotEmpty() || $manualHasErrors)
                        <form id="manual-attendance-form" method="POST" action="{{ route('events.attendances.manual', $event) }}">
                            @csrf
                            <div class="mb-3">
                                <label for="employee_id" class="form-label">Pegawai <span class="text-danger" aria-hidden="true">*</span></label>
                                <select id="employee_id" name="employee_id" class="form-select @error('employee_id') is-invalid @enderror" required @error('employee_id') aria-invalid="true" aria-describedby="employee_id-error" @enderror>
                                    <option value="">Pilih pegawai</option>
                                    @foreach ($manualEmployees as $employee)
                                        <option value="{{ $employee->id }}" @selected((string) old('employee_id') === (string) $employee->id)>{{ $employee->full_name }} - {{ $employee->employee_number }}{{ $employee->institution ? ' - '.$employee->institution->name : '' }}</option>
                                    @endforeach
                                </select>
                                @error('employee_id')<div id="employee_id-error" class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <label for="note" class="form-label">Alasan / Catatan</label>
                            <textarea id="note" name="note" class="form-control @error('note') is-invalid @enderror" rows="3" maxlength="1000" placeholder="Contoh: QR Code tidak terbaca" @error('note') aria-invalid="true" aria-describedby="note-error" @enderror>{{ old('note') }}</textarea>
                            @error('note')<div id="note-error" class="invalid-feedback">{{ $message }}</div>@enderror
                        </form>
                    @else
                        <p class="text-muted mb-0">Semua peserta yang memenuhi syarat sudah tercatat hadir.</p>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Batal</button>
                    @if ($manualEmployees->isNotEmpty() || $manualHasErrors)
                        <button type="submit" form="manual-attendance-form" class="btn btn-primary"><i class="ti ti-device-floppy" aria-hidden="true"></i> Simpan Kehadiran</button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <aside class="offcanvas offcanvas-end scanner-recent-panel" id="recent-attendance-panel" tabindex="-1" aria-labelledby="recent-scan-heading">
        <div class="offcanvas-header">
            <h2 id="recent-scan-heading" class="offcanvas-title h5">Kehadiran Terbaru</h2>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
        </div>
        <div class="offcanvas-body p-0">
            <div class="list-group list-group-flush" data-recent-scans>
                @forelse ($recentAttendances as $attendance)
                    <div class="list-group-item scanner-recent-item">
                        <strong>{{ $attendance->employee?->full_name ?? 'Pegawai tidak tersedia' }}</strong>
                        <small>NUP {{ $attendance->employee?->formatted_employee_number ?? 'Belum diisi' }} &middot; {{ $attendance->employee?->institution?->name ?? 'Unit belum diisi' }}</small>
                        <div class="d-flex flex-wrap align-items-center gap-2 mt-2"><span class="badge {{ $attendance->scan_method === 'manual' ? 'bg-light-warning text-warning' : 'bg-light-success text-success' }}">{{ $attendance->scan_method_label }}</span><small>{{ $attendance->scanned_at?->format('H:i:s') ?? '-' }} WIB</small></div>
                    </div>
                @empty
                    <div class="list-group-item text-muted py-3" data-recent-empty>Belum ada data kehadiran pada kegiatan ini.</div>
                @endforelse
            </div>
        </div>
        <div class="p-3 border-top"><a href="{{ route('events.attendances.index', $event) }}" class="btn btn-light-primary w-100">Lihat Daftar Kehadiran</a></div>
    </aside>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/attendance-scanner.js') }}"></script>
@endpush
