@extends('layouts.admin')

@section('title', 'Scan Absensi Barcode | YAPISTA HRIS')

@section('content')
    @php
        $dashboardRoute = Auth::user()?->isPanitia() ? 'scanner.dashboard' : 'dashboard';
    @endphp

    <div class="page-header">
        <div class="page-block">
            <div class="row align-items-center">
                <div class="col-md-12">
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route($dashboardRoute) }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('events.attendances.index', $event) }}">Daftar Hadir</a></li>
                        <li class="breadcrumb-item" aria-current="page">Scan Barcode</li>
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
                    <h4 class="mb-1">Scan Absensi Barcode</h4>
                    <p class="mb-0 text-muted">Scan barcode ID Card pegawai. Scanner fisik akan mengisi NUP / Nomor Pegawai 10 digit lalu menekan Enter otomatis.</p>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('events.attendances.index', $event) }}" class="btn btn-light-primary">
                        <i class="ti ti-list"></i>
                        Daftar Hadir
                    </a>
                    <a href="{{ route($dashboardRoute) }}" class="btn btn-light-secondary">Dashboard</a>
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
                            <span class="badge bg-light-success text-success">{{ $event->status_label }}</span>
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
                    <h5 class="mb-0">Ringkasan</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total peserta</span>
                        <strong>{{ $totalParticipants }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Sudah hadir</span>
                        <strong class="text-success">{{ $attendedCount }}</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Belum hadir</span>
                        <strong class="text-warning">{{ $absentCount }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mt-2 pt-2 border-top">
                        <span>Persentase</span>
                        <strong>{{ $attendancePercentage }}%</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Input Scanner Barcode</h5>
                </div>
                <div class="card-body">
                    <form id="barcode-scan-form" method="POST" action="{{ route('events.scan', $event) }}">
                        @csrf
                        <div class="form-group mb-3">
                            <label for="employee_number" class="form-label">NUP / Nomor Pegawai</label>
                            <input
                                id="employee_number"
                                type="text"
                                name="employee_number"
                                class="form-control form-control-lg text-center fw-semibold"
                                inputmode="numeric"
                                autocomplete="off"
                                placeholder="7770923822"
                                autofocus
                                required
                            >
                            <small class="text-muted d-block mt-2">Sistem akan mengambil hanya angka dari hasil scan, misalnya teks "Call 777 0923822" menjadi 7770923822.</small>
                        </div>

                        <button type="submit" class="btn btn-primary" data-scan-submit>
                            <i class="ti ti-barcode"></i>
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
                                <textarea id="note" name="note" rows="3" class="form-control" placeholder="Contoh: barcode rusak atau scanner bermasalah"></textarea>
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
        const employeeNumberInput = document.getElementById('employee_number');
        const scanForm = document.getElementById('barcode-scan-form');

        const focusScannerInput = () => {
            if (!employeeNumberInput) {
                return;
            }

            employeeNumberInput.focus();
            employeeNumberInput.select();
        };

        const scanSubmitButton = scanForm?.querySelector('[data-scan-submit]');

        const resetScannerForm = () => {
            if (scanForm) {
                scanForm.dataset.submitting = 'false';
            }

            if (scanSubmitButton) {
                scanSubmitButton.disabled = false;
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

            employeeNumberInput.value = employeeNumberInput.value.replace(/\D+/g, '');
            scanForm.dataset.submitting = 'true';

            if (scanSubmitButton) {
                scanSubmitButton.disabled = true;
            }
        });
    </script>
@endpush
