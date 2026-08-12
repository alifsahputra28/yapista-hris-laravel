@extends('layouts.admin')

@section('title', 'Detail Kegiatan | YAPISTA HRIS')

@section('content')
    <x-page-header
        :title="$event->name"
        subtitle="Detail kegiatan yang Anda ikuti."
        :breadcrumbs="[
            ['label' => 'Beranda', 'url' => route('pegawai.dashboard')],
            ['label' => 'Kegiatan', 'url' => route('pegawai.activities.index')],
            ['label' => 'Detail'],
        ]"
    >
        <x-slot:actions><a href="{{ route('pegawai.activities.index') }}" class="btn btn-light-secondary"><i class="ti ti-arrow-left" aria-hidden="true"></i> Kembali</a></x-slot:actions>
    </x-page-header>

    <section class="card" aria-labelledby="event-information-heading">
        <div class="card-header d-flex align-items-start justify-content-between gap-3">
            <h2 id="event-information-heading" class="h5 mb-0">Informasi Kegiatan</h2>
            <span class="badge bg-light-primary text-primary">{{ $participant->participant_status_label }}</span>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6"><span class="text-muted small d-block mb-1">Tanggal</span><strong>{{ $event->event_date?->format('d M Y') ?? '-' }}</strong></div>
                <div class="col-md-6"><span class="text-muted small d-block mb-1">Waktu</span><strong>{{ $event->start_time?->format('H:i') ?? '-' }}{{ $event->end_time ? ' - '.$event->end_time->format('H:i') : '' }} WIB</strong></div>
                <div class="col-md-6"><span class="text-muted small d-block mb-1">Lokasi</span><strong>{{ $event->location ?: 'Belum ditentukan' }}</strong></div>
                <div class="col-md-6"><span class="text-muted small d-block mb-1">Kehadiran</span><strong>{{ $attendance?->attendance_status_label ?? 'Belum hadir' }}</strong></div>
                @if (filled($event->description))
                    <div class="col-12"><span class="text-muted small d-block mb-1">Keterangan</span><p class="mb-0">{{ $event->description }}</p></div>
                @endif
            </div>
        </div>
    </section>
@endsection
