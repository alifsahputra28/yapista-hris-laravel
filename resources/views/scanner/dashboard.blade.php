@extends('layouts.admin')

@section('title', 'Dashboard Panitia Scanner | YAPISTA HRIS')

@section('content')
    @php
        $activeEvents = class_exists(\App\Models\Event::class)
            ? \App\Models\Event::query()->where('status', 'active')->count()
            : 0;
    @endphp

    <div class="page-header">
        <div class="page-block">
            <div class="row align-items-center">
                <div class="col-md-12">
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item" aria-current="page">Scanner</li>
                        <li class="breadcrumb-item" aria-current="page">Dashboard</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card page-intro-card">
                <div class="card-body">
                    <h4 class="mb-2">Dashboard Panitia Scanner</h4>
                    <p class="mb-0 text-muted">Area kerja panitia untuk proses scan dan kehadiran kegiatan yayasan.</p>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card summary-card">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avtar avtar-s bg-light-success text-success">
                            <i class="ti ti-calendar-event f-20"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Kegiatan Aktif</div>
                            <h4 class="mb-0">{{ number_format($activeEvents) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <h5 class="mb-1">Akses Scan</h5>
                    <p class="mb-0 text-muted">Fitur scan QR akan aktif pada tahap absensi kegiatan.</p>
                </div>
                <button type="button" class="btn btn-light-secondary" disabled>
                    <i class="ti ti-qrcode"></i>
                    Scanner Belum Aktif
                </button>
            </div>
        </div>
    </div>
@endsection
