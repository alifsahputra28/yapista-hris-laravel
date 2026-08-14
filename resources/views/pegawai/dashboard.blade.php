@extends('layouts.admin')

@section('title', 'Beranda | YAPISTA HRIS')

@section('content')
    <div class="d-none d-lg-block">
        <x-page-header
            title="Beranda"
            :subtitle="'Selamat datang, '.str(Auth::user()->name)->before(' ').'.'"
            :breadcrumbs="[['label' => 'Beranda']]"
        />
    </div>

    <div class="d-lg-none mb-4">
        <p class="text-muted mb-1">Selamat datang,</p>
        <h1 class="h4 mb-0">{{ str(Auth::user()->name)->before(' ') }}</h1>
    </div>

    @if (! $employee)
        <div class="alert alert-warning">Data pegawai belum terhubung dengan akun Anda. Hubungi HR/Admin.</div>
    @else
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if ($employee->verification_status === 'rejected' && filled($employee->verification_note))
            <div class="alert alert-danger d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div><strong>Data perlu diperbaiki.</strong> {{ $employee->verification_note }}</div>
                <a href="{{ route('pegawai.profile.wizard.index') }}" class="btn btn-sm btn-danger">Perbarui Data</a>
            </div>
        @elseif ($employee->isSubmitted())
            <div class="alert alert-warning mb-3">Data Anda sedang diperiksa HR. Perubahan profil sementara dikunci.</div>
        @endif

        <div class="d-lg-none">
            <section class="mb-4" aria-labelledby="mobile-id-card-heading">
                <div class="d-flex align-items-center justify-content-between gap-3 mb-2">
                    <h2 id="mobile-id-card-heading" class="h6 mb-0">ID Card Digital</h2>
                </div>
                <a href="{{ route('pegawai.id-card.show') }}" class="card text-body text-decoration-none mb-0">
                    <div class="card-body d-flex align-items-center justify-content-between gap-3">
                        <div class="flex-grow-1 overflow-hidden">
                            <strong class="d-block text-truncate">{{ $employee->full_name }}</strong>
                            <span class="text-muted small d-block text-truncate">
                                {{ $employee->position?->name ?? 'Jabatan belum ditetapkan' }}
                                <span aria-hidden="true">&bull;</span>
                                {{ $employee->institution?->name ?? 'Unit belum ditetapkan' }}
                            </span>
                            <span class="text-primary small d-inline-flex align-items-center gap-1 mt-3">Lihat ID Card <i class="ti ti-arrow-right" aria-hidden="true"></i></span>
                        </div>
                        @if ($qrCodeSvg)
                            <div class="employee-id-preview-qr flex-shrink-0" role="img" aria-label="QR Code ID Card">{!! $qrCodeSvg !!}</div>
                        @else
                            <span class="avtar avtar-l bg-light-secondary text-secondary flex-shrink-0"><i class="ti ti-qrcode" aria-hidden="true"></i></span>
                        @endif
                    </div>
                </a>
            </section>

            <section class="mb-4" aria-labelledby="mobile-upcoming-heading">
                <div class="d-flex align-items-center justify-content-between gap-3 mb-2">
                    <h2 id="mobile-upcoming-heading" class="h6 mb-0">Kegiatan Terdekat</h2>
                    <a href="{{ route('pegawai.activities.index') }}" class="small">Lihat</a>
                </div>
                @if ($nextEvent)
                    <a href="{{ route('pegawai.activities.show', $nextEvent) }}" class="d-block p-3 text-body text-decoration-none border rounded bg-white">
                        <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                            <strong>{{ $nextEvent->name }}</strong>
                            <span class="badge bg-light-primary text-primary">{{ $nextEvent->participants->first()?->participant_status_label ?? 'Peserta' }}</span>
                        </div>
                        <div class="text-muted small d-grid gap-1">
                            <span>{{ $nextEvent->event_date->locale('id')->translatedFormat('d M Y') }} &bull; {{ $nextEvent->start_time?->format('H:i') ?? '-' }} WIB</span>
                            <span>{{ $nextEvent->location ?: 'Lokasi belum ditentukan' }}</span>
                        </div>
                    </a>
                @else
                    <div class="py-2 text-muted small border-top">Belum ada kegiatan yang akan datang.</div>
                @endif
            </section>

            <section class="mb-3" aria-labelledby="mobile-attendance-heading">
                <h2 id="mobile-attendance-heading" class="h6 mb-2">Kehadiran Terakhir</h2>
                @if ($recentAttendances->isEmpty())
                    <div class="py-2 text-muted small border-top">Belum ada riwayat kehadiran.</div>
                @else
                    <div class="list-group list-group-flush border-top border-bottom">
                        @foreach ($recentAttendances as $attendance)
                            <div class="list-group-item px-0 py-3 bg-transparent">
                                <div class="d-flex align-items-center justify-content-between gap-3">
                                    <div class="d-flex align-items-start gap-2">
                                        <i class="ti ti-circle-check text-success mt-1" aria-hidden="true"></i>
                                        <div>
                                            <strong class="d-block">{{ $attendance->event?->name ?? 'Kegiatan tidak tersedia' }}</strong>
                                            <span class="text-muted small">{{ $attendance->scanned_at?->locale('id')->translatedFormat('d M Y') ?? '-' }}</span>
                                        </div>
                                    </div>
                                    <span class="badge bg-light-success text-success">{{ $attendance->attendance_status_label }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>

        <div class="d-none d-lg-block">
            <div class="row g-3">
                <div class="col-xl-7">
                    <section class="card h-100" aria-labelledby="upcoming-event-heading">
                        <div class="card-header d-flex align-items-start justify-content-between gap-3">
                            <div><h2 id="upcoming-event-heading" class="h5 mb-1">Kegiatan Terdekat</h2><p class="text-muted mb-0">Agenda aktif yang telah mencantumkan Anda sebagai peserta.</p></div>
                            <a href="{{ route('pegawai.activities.index') }}" class="btn btn-sm btn-light-primary">Lihat</a>
                        </div>
                        <div class="card-body">
                            @if ($nextEvent)
                                <div class="d-flex align-items-start justify-content-between gap-3">
                                    <div><h3 class="h5 mb-2">{{ $nextEvent->name }}</h3><div class="d-grid gap-2 text-muted"><span><i class="ti ti-calendar me-2"></i>{{ $nextEvent->event_date->locale('id')->translatedFormat('d M Y') }}</span><span><i class="ti ti-clock me-2"></i>{{ $nextEvent->start_time?->format('H:i') ?? '-' }} WIB</span><span><i class="ti ti-map-pin me-2"></i>{{ $nextEvent->location ?: 'Lokasi belum ditentukan' }}</span></div></div>
                                    <span class="badge bg-light-primary text-primary">{{ $nextEvent->participants->first()?->participant_status_label ?? 'Peserta' }}</span>
                                </div>
                            @else
                                <p class="text-muted mb-0">Belum ada kegiatan yang akan datang.</p>
                            @endif
                        </div>
                    </section>
                </div>
                <div class="col-xl-5">
                    <section class="card h-100" aria-labelledby="employee-action-heading">
                        <div class="card-header"><h2 id="employee-action-heading" class="h5 mb-0">ID Card Digital</h2></div>
                        <div class="card-body d-flex align-items-center justify-content-between gap-3">
                            <div><h3 class="h6 mb-1">Kartu pegawai siap digunakan</h3><p class="text-muted mb-3">Gunakan QR Code untuk absensi kegiatan.</p><a href="{{ route('pegawai.id-card.show') }}" class="btn btn-primary">Buka ID Card</a></div>
                            @if ($qrCodeSvg)<div class="employee-id-preview-qr" role="img" aria-label="QR Code ID Card">{!! $qrCodeSvg !!}</div>@endif
                        </div>
                    </section>
                </div>
                <div class="col-12">
                    <section class="card" aria-labelledby="recent-attendance-heading">
                        <div class="card-header"><h2 id="recent-attendance-heading" class="h5 mb-0">Kehadiran Terakhir</h2></div>
                        @if ($recentAttendances->isEmpty())
                            <div class="card-body"><p class="text-muted mb-0">Belum ada riwayat kehadiran.</p></div>
                        @else
                            <div class="list-group list-group-flush">@foreach ($recentAttendances as $attendance)<div class="list-group-item px-4 py-3"><div class="d-flex align-items-center justify-content-between gap-2"><div><strong class="d-block">{{ $attendance->event?->name ?? 'Kegiatan tidak tersedia' }}</strong><span class="text-muted small">{{ $attendance->scanned_at?->locale('id')->translatedFormat('d M Y, H:i') ?? '-' }} WIB</span></div><span class="badge bg-light-success text-success">{{ $attendance->attendance_status_label }}</span></div></div>@endforeach</div>
                        @endif
                    </section>
                </div>
            </div>
        </div>
    @endif
@endsection
