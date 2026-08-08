@extends('layouts.admin')

@section('title', 'Scan Absensi QR Code | YAPISTA HRIS')

@section('content')
    @php
        $dashboardRoute = Auth::user()?->isPanitia() ? 'scanner.dashboard' : 'dashboard';
    @endphp

    <x-page-header
        title="Scan QR Code"
        subtitle="Pindai ID Card menggunakan scanner QR/2D."
        badge-label="{{ $event->status_label }}"
        badge-class="bg-light-success text-success"
        :breadcrumbs="[
            ['label' => 'Dashboard', 'url' => route($dashboardRoute)],
            ['label' => 'Daftar Hadir', 'url' => route('events.attendances.index', $event)],
            ['label' => 'Scan QR Code'],
        ]"
    >
        <x-slot:meta><span>{{ $event->name }}</span><span aria-hidden="true">&bull;</span><span>{{ $event->event_date?->format('d M Y') }}</span><span aria-hidden="true">&bull;</span><span>{{ $event->location ?? 'Lokasi belum diisi' }}</span></x-slot:meta>
        <x-slot:actions><a href="{{ route('events.attendances.index', $event) }}" class="btn btn-light-secondary"><i class="ti ti-list" aria-hidden="true"></i> Daftar Hadir</a></x-slot:actions>
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
        <div class="metric-item"><div class="metric-label">Peserta Aktif</div><div class="metric-value">{{ $totalParticipants }}</div></div>
        <div class="metric-item"><div class="metric-label">Sudah Hadir</div><div class="metric-value">{{ $attendedCount }}</div></div>
        <div class="metric-item"><div class="metric-label">Belum Hadir</div><div class="metric-value">{{ $absentCount }}</div></div>
        <div class="metric-item"><div class="metric-label">Kehadiran</div><div class="metric-value">{{ $attendancePercentage }}%</div></div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Input Scanner QR/2D</h5>
                </div>
                <div class="card-body">
                    <form id="qr-scan-form" method="POST" action="{{ route('events.scan', $event) }}">
                        @csrf
                        <div class="form-group mb-3">
                            <label for="qr_payload" class="form-label">QR Code ID Card</label>
                            <input
                                id="qr_payload"
                                type="text"
                                name="qr_payload"
                                class="form-control form-control-lg text-center fw-semibold"
                                autocomplete="off"
                                maxlength="128"
                                placeholder="Pindai QR Code ID Card pegawai..."
                                autofocus
                                required
                            >
                            <small class="text-muted d-block mt-2">Scanner QR/2D akan mengetik payload lalu menekan Enter secara otomatis. Kamera browser tidak digunakan.</small>
                        </div>

                        <button type="submit" class="btn btn-primary" data-scan-submit>
                            <i class="ti ti-qrcode"></i>
                            Proses Absensi
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Input Manual Peserta</h5>
                </div>
                <div class="card-body">
                    @if ($manualEmployees->isNotEmpty())
                        <form method="POST" action="{{ route('events.attendances.manual', $event) }}">
                            @csrf
                            <div class="form-group mb-3">
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
                            <div class="form-group mb-3">
                                <label for="note" class="form-label">Catatan</label>
                                <textarea id="note" name="note" rows="3" class="form-control" placeholder="Contoh: QR Code rusak atau scanner bermasalah"></textarea>
                            </div>
                            <button type="submit" class="btn btn-light-primary">
                                <i class="ti ti-user-plus"></i>
                                Simpan Manual
                            </button>
                        </form>
                    @else
                        <div class="alert alert-light border mb-0">Semua peserta yang memenuhi syarat sudah tercatat hadir.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const qrPayloadInput = document.getElementById('qr_payload');
        const scanForm = document.getElementById('qr-scan-form');

        const focusScannerInput = () => {
            if (!qrPayloadInput) {
                return;
            }

            qrPayloadInput.focus();
            qrPayloadInput.select();
        };

        const scanSubmitButton = scanForm?.querySelector('[data-scan-submit]');

        const resetScannerForm = () => {
            if (scanForm) {
                scanForm.dataset.submitting = 'false';
            }

            if (scanSubmitButton) {
                scanSubmitButton.disabled = false;
            }

            if (qrPayloadInput) {
                qrPayloadInput.value = '';
            }

            focusScannerInput();
        };

        window.addEventListener('load', resetScannerForm);
        window.addEventListener('pageshow', resetScannerForm);

        scanForm?.addEventListener('submit', (event) => {
            if (scanForm.dataset.submitting === 'true') {
                event.preventDefault();
                return;
            }

            scanForm.dataset.submitting = 'true';

            if (scanSubmitButton) {
                scanSubmitButton.disabled = true;
            }
        });
    </script>
@endpush
