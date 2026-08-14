@extends('layouts.admin')

@section('title', 'ID Card Saya | YAPISTA HRIS')

@section('content')
    <div class="d-none d-lg-block">
        <x-page-header
            title="ID Card Saya"
            subtitle="Kartu pegawai digital dan QR Code absensi kegiatan."
            :breadcrumbs="[['label' => 'Beranda', 'url' => route('pegawai.dashboard')], ['label' => 'ID Card']]"
        />
    </div>

    <h1 class="h4 d-lg-none mb-3">ID Card Saya</h1>

    @if (session('error'))
        <div class="alert alert-warning">{{ session('error') }}</div>
    @endif

    @foreach ($warnings as $warning)
        <div class="alert alert-warning">{{ $warning }}</div>
    @endforeach

    <section class="employee-e-card-stage" aria-label="ID Card digital saya">
        @if ($employee && $isReadyForIdCard)
            <x-employee-e-card :employee="$employee" :qr-code-svg="$qrCodeSvg" />

            <div class="d-flex flex-wrap justify-content-center gap-2 mt-3">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#employee-qr-modal">
                    <i class="ti ti-qrcode" aria-hidden="true"></i>
                    Tampilkan QR Besar
                </button>
                <a href="{{ route('pegawai.id-card.download') }}" class="btn btn-light-secondary">
                    <i class="ti ti-download" aria-hidden="true"></i>
                    Download
                </a>
            </div>
        @else
            <div class="card employee-e-card-unavailable mb-0">
                <div class="card-body text-center py-4">
                    <div class="avtar avtar-l bg-light-warning text-warning mx-auto mb-3">
                        <i class="ti ti-id f-28" aria-hidden="true"></i>
                    </div>
                    <h2 class="h5 mb-1">ID Card belum tersedia.</h2>
                    <p class="text-muted mb-0">ID Card akan tersedia setelah pegawai mendapatkan NUP dan proses verifikasi selesai.</p>
                </div>
            </div>
        @endif
    </section>

    @if ($employee && $isReadyForIdCard)
        <div class="modal fade" id="employee-qr-modal" tabindex="-1" aria-labelledby="employee-qr-modal-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered mx-3 mx-sm-auto">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title h5" id="employee-qr-modal-title">QR Code Saya</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body text-center p-4">
                        <div class="employee-e-card-qr-modal mx-auto" role="img" aria-label="QR Code absensi pegawai">{!! $qrCodeSvg !!}</div>
                        <p class="fw-semibold mt-3 mb-1">NUP {{ $employee->employee_number }}</p>
                        <p class="text-muted small mb-0">Arahkan QR Code ini ke scanner kegiatan.</p>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
