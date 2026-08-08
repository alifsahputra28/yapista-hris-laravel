@extends('layouts.admin')

@section('title', 'ID Card Saya | YAPISTA HRIS')

@push('styles')
    <style>
        .id-card-preview-wrap {
            display: flex;
            justify-content: center;
            padding: 1.5rem;
            background: #f8fafc;
            border-radius: 8px;
        }

        .yapista-id-card {
            width: 360px;
            max-width: 100%;
            overflow: hidden;
            border: 1px solid #dbe3ea;
            border-radius: 12px;
            background: #ffffff;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
        }

        .id-card-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            color: #ffffff;
            background: #1d4ed8;
        }

        .id-card-logo {
            width: 54px;
            height: 54px;
            padding: 0.35rem;
            object-fit: contain;
            border-radius: 8px;
            background: #ffffff;
        }

        .id-card-brand {
            font-size: 1.25rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .id-card-subtitle {
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            opacity: 0.9;
        }

        .id-card-body {
            padding: 1.25rem 1rem;
            text-align: center;
        }

        .id-card-photo {
            width: 104px;
            height: 104px;
            margin-bottom: 0.85rem;
            object-fit: cover;
            border: 4px solid #eef2f7;
            border-radius: 50%;
        }

        .id-card-name {
            font-size: 1.1rem;
            font-weight: 700;
            color: #111827;
        }

        .id-card-number {
            margin-top: 0.25rem;
            color: #5b6b79;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .id-card-info {
            display: grid;
            gap: 0.55rem;
            margin-top: 1rem;
            text-align: left;
        }

        .id-card-info div {
            padding: 0.65rem 0.75rem;
            border-radius: 8px;
            background: #f8fafc;
        }

        .id-card-info span {
            display: block;
            color: #64748b;
            font-size: 0.73rem;
        }

        .id-card-info strong {
            display: block;
            margin-top: 0.1rem;
            color: #111827;
            font-size: 0.88rem;
        }

        .id-card-qr {
            padding: 1rem;
            text-align: center;
            border-top: 1px dashed #cbd5e1;
            background: #f8fafc;
        }

        .qr-code-svg {
            width: 150px;
            max-width: 100%;
            margin: 0 auto;
            padding: 6px;
            background: #ffffff;
        }

        .qr-code-svg svg {
            display: block;
            width: 100%;
            height: auto;
        }

        .qr-code-placeholder {
            display: grid;
            width: 150px;
            min-height: 150px;
            margin: 0 auto;
            place-items: center;
            color: #64748b;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            background: #ffffff;
        }

        .qr-code-placeholder i {
            font-size: 1.5rem;
        }

        .qr-code-label {
            margin-top: 0.4rem;
            color: #111827;
            font-size: 0.78rem;
            font-weight: 700;
        }

        .qr-code-note {
            margin-top: 0.15rem;
            color: #64748b;
            font-size: 0.75rem;
        }
    </style>
@endpush

@section('content')
    <div class="page-header">
        <div class="page-block">
            <div class="row align-items-center">
                <div class="col-md-12">
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('pegawai.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item" aria-current="page">ID Card Saya</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    @if (session('error'))
        <div class="alert alert-warning">{{ session('error') }}</div>
    @endif

    <div class="card page-intro-card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <h4 class="mb-1">ID Card Saya</h4>
                    <p class="mb-0 text-muted">Kartu pegawai dan QR Code untuk absensi kegiatan.</p>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('pegawai.dashboard') }}" class="btn btn-light-secondary">Kembali</a>
                    <a href="{{ route('pegawai.id-card.download') }}" class="btn btn-primary">
                        <i class="ti ti-download"></i>
                        Download
                    </a>
                </div>
            </div>
        </div>
    </div>

    @foreach ($warnings as $warning)
        <div class="alert alert-warning">{{ $warning }}</div>
    @endforeach

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Preview Kartu</h5>
        </div>
        <div class="card-body">
            @if ($employee && $isValidForIdCard)
                <div class="id-card-preview-wrap">
                    @include('id-cards._card', [
                        'employee' => $employee,
                        'qrCodeSvg' => $qrCodeSvg,
                    ])
                </div>
            @else
                <div class="empty-state">
                    <div class="avtar avtar-l bg-light-warning text-warning">
                        <i class="ti ti-id-off f-28"></i>
                    </div>
                    <h5 class="mb-1">ID Card belum tersedia.</h5>
                    <p class="text-muted mb-0">ID Card akan tampil setelah data Anda terhubung, terverifikasi, dan memiliki NUP / Nomor Pegawai valid.</p>
                </div>
            @endif
        </div>
    </div>
@endsection
