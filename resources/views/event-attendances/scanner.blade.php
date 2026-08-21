@extends('layouts.admin')

@section('layout-mode', 'scanner')
@section('title', 'Scan Kehadiran | YAPISTA HRIS')

@section('content')
    @php
        $exitRoute = Auth::user()?->isPanitia()
            ? route('scanner.dashboard')
            : route('events.show', $event);
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
        @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    </div>

    <div class="metric-strip scanner-metric-strip" aria-label="Ringkasan kehadiran">
        <div class="metric-item"><div class="metric-label">Total Peserta</div><div class="metric-value" data-metric="participants">{{ $totalParticipants }}</div></div>
        <div class="metric-item"><div class="metric-label">Sudah Hadir</div><div class="metric-value" data-metric="attended">{{ $attendedCount }}</div></div>
        <div class="metric-item"><div class="metric-label">Belum Hadir</div><div class="metric-value" data-metric="absent">{{ $absentCount }}</div></div>
        <div class="metric-item"><div class="metric-label">Tingkat Kehadiran</div><div class="metric-value" data-metric="percentage">{{ $attendancePercentage }}%</div></div>
    </div>

    <div class="row g-3 scanner-workspace">
        <div class="col-12 col-xl-7">
            <section class="card scanner-primary-panel h-100 mb-0" aria-labelledby="scanner-input-heading">
                <div class="card-header">
                    <div>
                        <h2 id="scanner-input-heading" class="h5 mb-1">Scan ID Card Pegawai</h2>
                        <p class="text-muted small mb-0">Arahkan scanner ke QR Code pada ID Card pegawai.</p>
                    </div>
                </div>
                <div class="card-body d-flex flex-column">
                    <div class="scanner-state is-ready" data-scanner-state role="status" aria-live="polite">
                        <span class="scanner-state-icon"><i class="ti ti-scan" data-state-icon aria-hidden="true"></i></span>
                        <span>
                            <strong data-state-title>Siap menerima scanner</strong>
                            <small data-state-message>Arahkan scanner ke QR Code pada ID Card pegawai.</small>
                        </span>
                    </div>

                    <form id="qr-scan-form" class="scanner-input-form" method="POST" action="{{ route('events.scan', $event) }}">
                        @csrf
                        <label for="qr_payload" class="form-label">QR Code Pegawai</label>
                        <input
                            id="qr_payload"
                            type="text"
                            name="qr_payload"
                            class="form-control form-control-lg text-center fw-semibold"
                            autocomplete="off"
                            maxlength="128"
                            placeholder="Pindai QR Code pegawai..."
                            autofocus
                            required
                        >
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3">
                            <small class="text-muted">Mendukung ID Card digital dan kartu pegawai lama.</small>
                            <button type="submit" class="btn btn-primary" data-scan-submit>
                                <i class="ti ti-qrcode" aria-hidden="true"></i> Proses Kehadiran
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        </div>

        <div class="col-12 col-xl-5">
            <section class="card scanner-result-panel h-100 mb-0" aria-labelledby="last-scan-heading">
                <div class="card-header"><h2 id="last-scan-heading" class="h5 mb-0">Hasil Scan Terakhir</h2></div>
                <div class="card-body">
                    <div class="scanner-result-empty" data-last-scan-empty>
                        <i class="ti ti-id-badge-2" aria-hidden="true"></i>
                        <p class="mb-0">Hasil identitas akan tampil setelah QR dipindai.</p>
                    </div>
                    <div class="scanner-result" data-last-scan hidden>
                        <div class="scanner-result-avatar"><i class="ti ti-user" aria-hidden="true"></i></div>
                        <div class="scanner-result-identity">
                            <strong data-result-name>-</strong>
                            <span>NUP <span data-result-number>-</span></span>
                            <span data-result-unit>-</span>
                            <span data-result-position>-</span>
                        </div>
                        <div class="scanner-result-status">
                            <span class="badge bg-light-secondary text-secondary" data-result-status>-</span>
                            <small data-result-time>-</small>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-12">
            <section class="card scanner-recent-panel mb-0" aria-labelledby="recent-scan-heading">
                <div class="card-header"><h2 id="recent-scan-heading" class="h5 mb-0">Kehadiran Terbaru</h2></div>
                <div class="list-group list-group-flush" data-recent-scans>
                    @forelse ($recentAttendances as $attendance)
                        <div class="list-group-item scanner-recent-item">
                            <div><strong>{{ $attendance->employee?->full_name ?? 'Pegawai tidak tersedia' }}</strong><small>NUP {{ $attendance->employee?->formatted_employee_number ?? 'Belum diisi' }} &middot; {{ $attendance->employee?->institution?->name ?? 'Unit belum diisi' }}</small></div>
                            <div><span class="badge {{ $attendance->scan_method === 'manual' ? 'bg-light-warning text-warning' : 'bg-light-success text-success' }}">{{ $attendance->scan_method_label }}</span><small>{{ $attendance->scanned_at?->locale('id')->translatedFormat('H:i:s') ?? '-' }} WIB</small></div>
                        </div>
                    @empty
                        <div class="list-group-item text-muted py-3" data-recent-empty>Belum ada data kehadiran pada kegiatan ini.</div>
                    @endforelse
                </div>
            </section>
        </div>

        <div class="col-12">
            <section class="card scanner-manual-panel mb-0">
                <div class="card-header scanner-manual-toggle-wrap">
                    <button class="btn btn-link text-decoration-none p-0" type="button" data-bs-toggle="collapse" data-bs-target="#manual-attendance-panel" aria-expanded="{{ $errors->any() ? 'true' : 'false' }}" aria-controls="manual-attendance-panel">
                        <span><i class="ti ti-user-plus" aria-hidden="true"></i> Kehadiran Manual</span>
                        <i class="ti ti-chevron-down" aria-hidden="true"></i>
                    </button>
                </div>
                <div id="manual-attendance-panel" class="collapse {{ $errors->any() ? 'show' : '' }}">
                    <div class="card-body">
                        @if ($manualEmployees->isNotEmpty())
                            <form method="POST" action="{{ route('events.attendances.manual', $event) }}">
                                @csrf
                                <div class="row g-3 align-items-end">
                                    <div class="col-12 col-lg-5">
                                        <label for="employee_id" class="form-label">Pilih Pegawai</label>
                                        <select id="employee_id" name="employee_id" class="form-select" required>
                                            <option value="">Pilih pegawai</option>
                                            @foreach ($manualEmployees as $employee)
                                                <option value="{{ $employee->id }}">{{ $employee->full_name }} - {{ $employee->employee_number }}{{ $employee->institution ? ' - '.$employee->institution->name : '' }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12 col-lg-5">
                                        <label for="note" class="form-label">Catatan</label>
                                        <input id="note" name="note" class="form-control" maxlength="1000" placeholder="Contoh: QR Code tidak terbaca atau scanner bermasalah">
                                    </div>
                                    <div class="col-12 col-lg-2"><button type="submit" class="btn btn-light-primary w-100"><i class="ti ti-device-floppy" aria-hidden="true"></i> Simpan Kehadiran</button></div>
                                </div>
                            </form>
                        @else
                            <div class="text-muted">Semua peserta yang memenuhi syarat sudah tercatat hadir.</div>
                        @endif
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const qrPayloadInput = document.getElementById('qr_payload');
            const scanForm = document.getElementById('qr-scan-form');
            const scanSubmitButton = scanForm?.querySelector('[data-scan-submit]');
            const statePanel = document.querySelector('[data-scanner-state]');
            const stateTitle = document.querySelector('[data-state-title]');
            const stateMessage = document.querySelector('[data-state-message]');
            const stateIcon = document.querySelector('[data-state-icon]');
            const lastScan = document.querySelector('[data-last-scan]');
            const lastScanEmpty = document.querySelector('[data-last-scan-empty]');
            const recentScans = document.querySelector('[data-recent-scans]');

            const focusScannerInput = () => {
                qrPayloadInput?.focus({ preventScroll: true });
                qrPayloadInput?.select();
            };

            const setState = (state, title, message) => {
                if (!statePanel) return;

                statePanel.classList.remove('is-ready', 'is-processing', 'is-success', 'is-warning', 'is-error');
                statePanel.classList.add(`is-${state}`);
                stateTitle.textContent = title;
                stateMessage.textContent = message;
                stateIcon.className = state === 'processing' ? 'ti ti-loader-2 scanner-spin' : ({
                    success: 'ti ti-circle-check',
                    warning: 'ti ti-alert-triangle',
                    error: 'ti ti-circle-x',
                    ready: 'ti ti-scan',
                }[state] || 'ti ti-scan');
            };

            const showLastScan = (employee, status, message) => {
                if (!employee || !lastScan || !lastScanEmpty) return;

                lastScanEmpty.hidden = true;
                lastScan.hidden = false;
                lastScan.querySelector('[data-result-name]').textContent = employee.full_name || '-';
                lastScan.querySelector('[data-result-number]').textContent = employee.employee_number || 'Belum diisi';
                lastScan.querySelector('[data-result-unit]').textContent = employee.institution || 'Unit belum diisi';
                lastScan.querySelector('[data-result-position]').textContent = employee.position || 'Jabatan belum diisi';

                const statusElement = lastScan.querySelector('[data-result-status]');
                statusElement.textContent = status === 'success' ? 'Hadir' : (status === 'already_attended' ? 'Sudah Hadir' : 'Ditolak');
                statusElement.className = `badge ${status === 'success' ? 'bg-light-success text-success' : (status === 'already_attended' ? 'bg-light-warning text-warning' : 'bg-light-danger text-danger')}`;
                lastScan.querySelector('[data-result-time]').textContent = employee.scanned_at ? `${employee.scanned_at} WIB` : message;
            };

            const addRecentScan = (employee, status) => {
                if (!recentScans || !employee || status !== 'success') return;

                recentScans.querySelector('[data-recent-empty]')?.remove();
                const item = document.createElement('div');
                item.className = 'list-group-item scanner-recent-item';

                const identity = document.createElement('div');
                const name = document.createElement('strong');
                const meta = document.createElement('small');
                name.textContent = employee.full_name || '-';
                meta.textContent = `NUP ${employee.employee_number || 'Belum diisi'} - ${employee.institution || 'Unit belum diisi'}`;
                identity.append(name, meta);

                const result = document.createElement('div');
                const badge = document.createElement('span');
                const time = document.createElement('small');
                badge.className = 'badge bg-light-success text-success';
                badge.textContent = 'QR Code';
                time.textContent = employee.scanned_at ? `${employee.scanned_at} WIB` : 'Baru saja';
                result.append(badge, time);

                item.append(identity, result);
                recentScans.prepend(item);
                while (recentScans.children.length > 5) recentScans.lastElementChild.remove();
            };

            const updateMetrics = () => {
                const participants = Number(document.querySelector('[data-metric="participants"]')?.textContent || 0);
                const attendedElement = document.querySelector('[data-metric="attended"]');
                const absentElement = document.querySelector('[data-metric="absent"]');
                const percentageElement = document.querySelector('[data-metric="percentage"]');
                const attended = Number(attendedElement?.textContent || 0) + 1;
                const absent = Math.max(participants - attended, 0);

                if (attendedElement) attendedElement.textContent = attended;
                if (absentElement) absentElement.textContent = absent;
                if (percentageElement) percentageElement.textContent = `${participants ? Math.round((attended / participants) * 1000) / 10 : 0}%`;
            };

            const resetScannerForm = () => {
                if (scanForm) scanForm.dataset.submitting = 'false';
                if (scanSubmitButton) scanSubmitButton.disabled = false;
                if (qrPayloadInput) qrPayloadInput.value = '';
                focusScannerInput();
            };

            window.addEventListener('load', resetScannerForm);
            window.addEventListener('pageshow', resetScannerForm);

            qrPayloadInput?.addEventListener('keydown', (event) => {
                if (event.key !== 'Enter') return;

                event.preventDefault();
                scanForm?.requestSubmit();
            });

            scanForm?.addEventListener('submit', async (event) => {
                if (!window.fetch) return;

                event.preventDefault();
                if (scanForm.dataset.submitting === 'true') return;

                scanForm.dataset.submitting = 'true';
                if (scanSubmitButton) scanSubmitButton.disabled = true;
                setState('processing', 'Memproses QR Code...', 'Mohon tunggu, identitas sedang diverifikasi.');

                try {
                    const response = await fetch(scanForm.action, {
                        method: 'POST',
                        body: new FormData(scanForm),
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    const data = await response.json();
                    const status = data.status || (data.success ? 'success' : 'rejected');

                    if (status === 'success') {
                        setState('success', 'Kehadiran berhasil dicatat', data.message);
                        updateMetrics();
                    } else if (status === 'already_attended') {
                        setState('warning', 'Kehadiran sudah tercatat', data.message);
                    } else {
                        setState('error', 'QR Code tidak dapat diproses', data.message || 'QR Code tidak dikenali.');
                    }

                    showLastScan(data.employee, status, data.message || '');
                    addRecentScan(data.employee, status);
                } catch (error) {
                    setState('error', 'Koneksi bermasalah', 'QR Code tidak dapat diproses. Silakan coba kembali.');
                } finally {
                    resetScannerForm();
                }
            });
        })();
    </script>
@endpush
