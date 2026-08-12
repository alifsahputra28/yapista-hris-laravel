@extends('layouts.admin')

@section('title', 'ID Card Pegawai | YAPISTA HRIS')

@section('content')
    <x-page-header
        title="ID Card Pegawai"
        subtitle="Kartu pegawai digital dan QR Code absensi kegiatan."
        :breadcrumbs="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Data Pegawai', 'url' => route('employees.index')],
            ['label' => 'Detail Pegawai', 'url' => route('employees.show', $employee)],
            ['label' => 'ID Card'],
        ]"
    >
        <x-slot:actions>
            <a href="{{ route('employees.show', $employee) }}" class="btn btn-light-secondary">
                <i class="ti ti-arrow-left" aria-hidden="true"></i>
                Kembali
            </a>
            @if ($isValidForIdCard && $hasActiveQrToken)
                <form method="POST" action="{{ route('employees.id-card.qr.regenerate', $employee) }}" onsubmit="return confirm('QR Code lama akan langsung tidak berlaku. Lanjutkan membuat QR baru?')">
                    @csrf
                    <button type="submit" class="btn btn-warning">
                        <i class="ti ti-refresh" aria-hidden="true"></i>
                        Buat Ulang QR Code
                    </button>
                </form>
            @elseif ($isValidForIdCard)
                <form method="POST" action="{{ route('employees.id-card.qr.generate', $employee) }}">
                    @csrf
                    <button type="submit" class="btn btn-success">
                        <i class="ti ti-qrcode" aria-hidden="true"></i>
                        Buat QR Code
                    </button>
                </form>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if (session('error'))
        <div class="alert alert-warning">{{ session('error') }}</div>
    @endif

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @foreach ($warnings as $warning)
        <div class="alert alert-warning">{{ $warning }}</div>
    @endforeach

    <section class="employee-e-card-stage" aria-label="Preview ID Card pegawai">
        @if ($isReadyForIdCard)
            <x-employee-e-card :employee="$employee" :qr-code-svg="$qrCodeSvg" />

            <div class="d-flex flex-wrap justify-content-center gap-2 mt-3">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#employee-qr-modal">
                    <i class="ti ti-qrcode" aria-hidden="true"></i>
                    Tampilkan QR Besar
                </button>
                <a href="{{ route('employees.id-card.download', $employee) }}" class="btn btn-light-secondary">
                    <i class="ti ti-download" aria-hidden="true"></i>
                    Download
                </a>
            </div>
        @else
            <div class="card employee-e-card-unavailable mb-0">
                <div class="card-body text-center py-4">
                    <div class="avtar avtar-l bg-light-warning text-warning mx-auto mb-3">
                        <i class="ti ti-id-off f-28" aria-hidden="true"></i>
                    </div>
                    <h2 class="h5 mb-1">ID Card belum tersedia.</h2>
                    <p class="fw-semibold mb-1">NUP {{ $employee->formatted_employee_number }}</p>
                    <p class="text-muted mb-0">ID Card akan tersedia setelah pegawai mendapatkan NUP, menyelesaikan proses verifikasi, dan memiliki QR Code aktif.</p>
                </div>
            </div>
        @endif
    </section>

    @if ($isReadyForIdCard)
        <div class="modal fade" id="employee-qr-modal" tabindex="-1" aria-labelledby="employee-qr-modal-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title h5" id="employee-qr-modal-title">QR Code Pegawai</h2>
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
