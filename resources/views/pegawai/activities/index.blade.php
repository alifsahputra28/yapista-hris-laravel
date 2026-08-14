@extends('layouts.admin')

@section('title', 'Kegiatan | YAPISTA HRIS')

@section('content')
    @php($activeTab = request('tab') === 'history' ? 'history' : 'upcoming')

    <x-page-header
        title="Kegiatan"
        subtitle="Agenda dan riwayat kehadiran kegiatan Anda."
        :breadcrumbs="[['label' => 'Beranda', 'url' => route('pegawai.dashboard')], ['label' => 'Kegiatan']]"
    />

    <ul class="nav nav-pills nav-fill bg-white border rounded p-1 mb-3" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab === 'upcoming' ? 'active' : '' }} w-100" id="upcoming-tab" data-bs-toggle="tab" data-bs-target="#upcoming-pane" type="button" role="tab" aria-controls="upcoming-pane" aria-selected="{{ $activeTab === 'upcoming' ? 'true' : 'false' }}">Akan Datang</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab === 'history' ? 'active' : '' }} w-100" id="history-tab" data-bs-toggle="tab" data-bs-target="#history-pane" type="button" role="tab" aria-controls="history-pane" aria-selected="{{ $activeTab === 'history' ? 'true' : 'false' }}">Riwayat</button>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade {{ $activeTab === 'upcoming' ? 'show active' : '' }}" id="upcoming-pane" role="tabpanel" aria-labelledby="upcoming-tab" tabindex="0">
            @if ($upcomingParticipants->isEmpty())
                <div class="py-3 text-muted border-top">Belum ada kegiatan yang akan datang.</div>
            @else
                <div class="row g-3">
                    @foreach ($upcomingParticipants as $participant)
                        <div class="col-12 col-xl-6">
                            <a href="{{ route('pegawai.activities.show', $participant->event) }}" class="card h-100 text-body text-decoration-none mb-0">
                                <div class="card-body">
                                    <div class="d-flex align-items-start justify-content-between gap-2 mb-3">
                                        <h2 class="h6 mb-0">{{ $participant->event->name }}</h2>
                                        <span class="badge bg-light-primary text-primary">{{ $participant->participant_status_label }}</span>
                                    </div>
                                    <div class="d-grid gap-2 text-muted small">
                                        <span><i class="ti ti-calendar me-2" aria-hidden="true"></i>{{ $participant->event->event_date?->locale('id')->translatedFormat('d M Y') ?? '-' }} &bull; {{ $participant->event->start_time?->format('H:i') ?? '-' }} WIB</span>
                                        <span><i class="ti ti-map-pin me-2" aria-hidden="true"></i>{{ $participant->event->location ?: 'Lokasi belum ditentukan' }}</span>
                                    </div>
                                    <span class="text-primary small d-inline-flex align-items-center gap-1 mt-3">Detail <i class="ti ti-arrow-right" aria-hidden="true"></i></span>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="tab-pane fade {{ $activeTab === 'history' ? 'show active' : '' }}" id="history-pane" role="tabpanel" aria-labelledby="history-tab" tabindex="0">
            @if ($attendanceHistory->isEmpty())
                <div class="py-3 text-muted border-top">Belum ada riwayat kehadiran.</div>
            @else
                <div class="list-group list-group-flush border-top border-bottom bg-white">
                    @foreach ($attendanceHistory as $attendance)
                        <a href="{{ $attendance->event ? route('pegawai.activities.show', $attendance->event) : '#' }}" class="list-group-item list-group-item-action px-3 py-3">
                            <div class="d-flex align-items-center justify-content-between gap-3">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="ti ti-circle-check text-success mt-1" aria-hidden="true"></i>
                                    <div>
                                        <strong class="d-block">{{ $attendance->event?->name ?? 'Kegiatan tidak tersedia' }}</strong>
                                        <span class="text-muted small">{{ $attendance->scanned_at?->locale('id')->translatedFormat('d M Y, H:i') ?? '-' }} WIB</span>
                                    </div>
                                </div>
                                <span class="badge bg-light-success text-success">{{ $attendance->attendance_status_label }}</span>
                            </div>
                        </a>
                    @endforeach
                </div>
                @if ($attendanceHistory->hasPages())
                    <div class="mt-3">
                        {{ $attendanceHistory->appends(['tab' => 'history'])->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            @endif
        </div>
    </div>
@endsection
