@extends('layouts.admin')

@section('title', 'Detail Kegiatan | YAPISTA HRIS')

@section('content')
    @php
        $targetTypes = \App\Models\Event::TARGET_TYPES;
        $statuses = \App\Models\Event::STATUSES;
        $participantStatuses = \App\Models\EventParticipant::STATUSES;
        $statusClasses = [
            'draft' => 'bg-light-secondary text-secondary',
            'active' => 'bg-light-success text-success',
            'closed' => 'bg-light-primary text-primary',
            'cancelled' => 'bg-light-danger text-danger',
        ];
        $participantStatusClasses = [
            'invited' => 'bg-light-warning text-warning',
            'confirmed' => 'bg-light-success text-success',
            'cancelled' => 'bg-light-danger text-danger',
        ];
        $selectedInstitutionIds = collect(old('institution_ids', []))->map(fn ($id) => (string) $id)->all();
        $selectedPositionIds = collect(old('position_ids', []))->map(fn ($id) => (string) $id)->all();
        $selectedEmployeeIds = collect(old('employee_ids', []))->map(fn ($id) => (string) $id)->all();
    @endphp

    <x-page-header
        title="{{ $event->name }}"
        subtitle="Detail jadwal, target, peserta, dan kesiapan absensi kegiatan."
        :breadcrumbs="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Kegiatan', 'url' => route('events.index')],
            ['label' => 'Detail'],
        ]"
        :badge-label="$statuses[$event->status] ?? $event->status"
        :badge-class="$statusClasses[$event->status] ?? 'bg-light-secondary text-secondary'"
    >
        <x-slot:meta>
            <span>{{ $event->event_date?->locale('id')->translatedFormat('d M Y') }}</span><span aria-hidden="true">•</span>
            <span>{{ $event->start_time?->format('H:i') ?? '-' }}{{ $event->end_time ? ' - '.$event->end_time->format('H:i') : '' }}</span><span aria-hidden="true">•</span>
            <span>{{ $event->location ?? 'Lokasi belum diisi' }}</span>
        </x-slot:meta>
        <x-slot:actions>
            <a href="{{ route('events.index') }}" class="btn btn-light-secondary">Kembali</a>
            <a href="{{ route('events.attendances.index', $event) }}" class="btn btn-light-primary"><i class="ti ti-list-check"></i> Daftar Hadir</a>
            @if ($event->canScanAttendance())
                <a href="{{ route('events.scanner', $event) }}" class="btn btn-success"><i class="ti ti-qrcode"></i> Scan QR</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Informasi Kegiatan</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-8 mb-3">
                            <small class="text-muted d-block">Nama Kegiatan</small>
                            <strong>{{ $event->name }}</strong>
                        </div>
                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Status</small>
                            <span class="badge {{ $statusClasses[$event->status] ?? 'bg-light-secondary text-secondary' }}">
                                {{ $statuses[$event->status] ?? $event->status }}
                            </span>
                        </div>
                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Tanggal</small>
                            {{ $event->event_date?->locale('id')->translatedFormat('d M Y') }}
                        </div>
                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Jam</small>
                            {{ $event->start_time?->format('H:i') ?? '-' }}
                            @if ($event->end_time)
                                - {{ $event->end_time->format('H:i') }}
                            @endif
                        </div>
                        <div class="col-md-4 mb-3">
                            <small class="text-muted d-block">Lokasi</small>
                            {{ $event->location ?? '-' }}
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block">Target</small>
                            {{ $event->target_type_label }}
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block">Dibuat Oleh</small>
                            {{ $event->creator?->name ?? '-' }}
                        </div>
                        <div class="col-12">
                            <small class="text-muted d-block">Deskripsi</small>
                            {{ $event->description ?? '-' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Ringkasan Peserta</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total peserta</span>
                        <strong>{{ $event->participants->count() }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Diundang</span>
                        <strong>{{ $participantCounts->get('invited', 0) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Confirmed</span>
                        <strong>{{ $participantCounts->get('confirmed', 0) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Cancelled</span>
                        <strong>{{ $participantCounts->get('cancelled', 0) }}</strong>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Aksi Kegiatan</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        @if ($event->canBeEdited())
                            <a href="{{ route('events.edit', $event) }}" class="btn btn-primary">
                                <i class="ti ti-edit"></i>
                                Edit
                            </a>
                        @endif

                        @if ($event->isDraft() && $event->participants->count() > 0)
                            <form method="POST" action="{{ route('events.activate', $event) }}" data-confirm-title="Aktifkan Kegiatan?" data-confirm-message="Kegiatan akan mulai menerima absensi peserta. Lanjutkan?">
                                @csrf
                                <button type="submit" class="btn btn-success">
                                    <i class="ti ti-player-play"></i>
                                    Activate
                                </button>
                            </form>
                        @endif

                        @if ($event->isActive())
                            <form method="POST" action="{{ route('events.close', $event) }}" data-confirm-title="Tutup Kegiatan?" data-confirm-message="Kegiatan yang ditutup tidak lagi menerima absensi baru. Lanjutkan?">
                                @csrf
                                <button type="submit" class="btn btn-primary">
                                    <i class="ti ti-lock"></i>
                                    Tutup
                                </button>
                            </form>
                        @endif

                        @if ($event->isDraft() || $event->isActive())
                            <form method="POST" action="{{ route('events.cancel', $event) }}" data-confirm-title="Batalkan Kegiatan?" data-confirm-message="Kegiatan akan ditandai dibatalkan. Lanjutkan?">
                                @csrf
                                <button type="submit" class="btn btn-danger">
                                    <i class="ti ti-x"></i>
                                    Batalkan
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($event->isDraft())
            <div class="row g-4">
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Generate Ulang Peserta</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('events.participants.generate', $event) }}" class="js-target-form" data-confirm-title="Generate Ulang Peserta?" data-confirm-message="Daftar peserta lama akan dihapus dan dibuat ulang sesuai target saat ini. Lanjutkan?">
                            @csrf
                        <div class="row g-3">
                                <div class="col-md-6 mb-3">
                                    <label for="show_target_type" class="form-label">Target Peserta</label>
                                    <select id="show_target_type" name="target_type" class="form-select js-target-type" required>
                                        @foreach ($targetTypes as $value => $label)
                                            <option value="{{ $value }}" @selected(old('target_type', $event->target_type) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12 mb-3" data-target-section="institution">
                                    <label for="show_institution_ids" class="form-label">Unit Kerja Target</label>
                                    <select id="show_institution_ids" name="institution_ids[]" class="form-select" multiple size="5">
                                        @foreach ($institutions as $institution)
                                            <option value="{{ $institution->id }}" @selected(in_array((string) $institution->id, $selectedInstitutionIds, true))>
                                                {{ $institution->name }}{{ $institution->level ? ' - '.$institution->level : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12 mb-3" data-target-section="position">
                                    <label for="show_position_ids" class="form-label">Jabatan Target</label>
                                    <select id="show_position_ids" name="position_ids[]" class="form-select" multiple size="5">
                                        @foreach ($positions as $position)
                                            <option value="{{ $position->id }}" @selected(in_array((string) $position->id, $selectedPositionIds, true))>
                                                {{ $position->name }}{{ $position->institution ? ' - '.$position->institution->name : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12 mb-3" data-target-section="selected">
                                    <label for="show_employee_ids" class="form-label">Pegawai Target</label>
                                    <select id="show_employee_ids" name="employee_ids[]" class="form-select" multiple size="6">
                                        @foreach ($eligibleEmployees as $employee)
                                            <option value="{{ $employee->id }}" @selected(in_array((string) $employee->id, $selectedEmployeeIds, true))>
                                                {{ $employee->full_name }} - {{ $employee->employee_number }}{{ $employee->institution ? ' - '.$employee->institution->name : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-refresh"></i>
                                Generate Ulang Peserta
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Tambah Peserta Manual</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('events.participants.manual', $event) }}">
                            @csrf
                            <div class="form-group mb-3">
                                <label for="manual_employee_ids" class="form-label">Pegawai</label>
                                <select id="manual_employee_ids" name="employee_ids[]" class="form-select" multiple size="8" required>
                                    @foreach ($eligibleEmployees as $employee)
                                        <option value="{{ $employee->id }}">
                                            {{ $employee->full_name }} - {{ $employee->employee_number }}{{ $employee->institution ? ' - '.$employee->institution->name : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-user-plus"></i>
                                Tambah Peserta
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <h5 class="mb-0">Daftar Peserta Kegiatan</h5>
                <a href="{{ route('events.participants.index', $event) }}" class="btn btn-light-primary">
                    <i class="ti ti-list"></i>
                    Kelola Peserta
                </a>
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
                            <th>Status Peserta</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($event->participants as $participant)
                            <tr>
                                <td class="ps-4">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $participant->employee?->full_name ?? '-' }}</div>
                                    <div class="data-meta">{{ $participant->employee?->employee_number ?? '-' }}</div>
                                </td>
                                <td>
                                    <div>{{ $participant->employee?->institution?->name ?? '-' }}</div>
                                    <div class="data-meta">{{ $participant->employee?->position?->name ?? '-' }}</div>
                                </td>
                                <td>
                                    <span class="badge {{ $participantStatusClasses[$participant->participant_status] ?? 'bg-light-secondary text-secondary' }}">
                                        {{ $participantStatuses[$participant->participant_status] ?? $participant->participant_status }}
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="table-actions">
                                        @if ($event->isDraft())
                                            <form method="POST" action="{{ route('event-participants.destroy', $participant) }}" data-confirm-title="Hapus Peserta?" data-confirm-message="Peserta akan dihapus dari kegiatan ini. Lanjutkan?">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-light-danger">
                                                    <i class="ti ti-trash"></i>
                                                    Hapus
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
                                <td colspan="5">
                                    <div class="empty-state">
                                        <div class="avtar avtar-l bg-light-secondary text-secondary">
                                            <i class="ti ti-users f-28"></i>
                                        </div>
                                        <h5 class="mb-1">Belum ada peserta kegiatan.</h5>
                                        <p class="text-muted mb-0">Generate peserta atau tambahkan peserta manual saat kegiatan masih draft.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('.js-target-form').forEach((form) => {
            const select = form.querySelector('.js-target-type');
            const sections = form.querySelectorAll('[data-target-section]');

            if (!select) {
                return;
            }

            const syncTargetSections = () => {
                sections.forEach((section) => {
                    section.classList.toggle('d-none', section.dataset.targetSection !== select.value);
                });
            };

            select.addEventListener('change', syncTargetSections);
            syncTargetSections();
        });
    </script>
@endpush
