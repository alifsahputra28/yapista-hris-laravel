@extends('layouts.admin')

@section('title', 'Dashboard Pegawai | YAPISTA HRIS')

@section('content')
    @php
        $employee = Auth::user()?->employee;
        $verificationStatuses = [
            'draft' => 'Draft',
            'submitted' => 'Menunggu Verifikasi',
            'verified' => 'Terverifikasi',
            'rejected' => 'Ditolak',
        ];
        $verificationClasses = [
            'draft' => 'bg-light-secondary text-secondary',
            'submitted' => 'bg-light-primary text-primary',
            'verified' => 'bg-light-success text-success',
            'rejected' => 'bg-light-danger text-danger',
        ];
        $documentsCount = $employee ? $employee->documents()->count() : 0;
        $ktpUploaded = $employee ? $employee->documents()->where('document_type', 'ktp')->exists() : false;
        $idCardRoute = Route::has('pegawai.id-card.show') ? route('pegawai.id-card.show') : null;
    @endphp

    <div class="page-header">
        <div class="page-block">
            <div class="row align-items-center">
                <div class="col-md-12">
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item" aria-current="page">Pegawai</li>
                        <li class="breadcrumb-item" aria-current="page">Dashboard</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if (! $employee)
        <div class="alert alert-warning">Data pegawai Anda belum terhubung. Silakan hubungi HR/Admin.</div>
    @else
        <div class="card page-intro-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <h4 class="mb-1">Dashboard Pegawai</h4>
                        <p class="mb-0 text-muted">Lengkapi biodata, unggah dokumen, dan pantau status verifikasi Anda.</p>
                    </div>

                    <span class="badge {{ $verificationClasses[$employee->verification_status] ?? 'bg-light-secondary text-secondary' }}">
                        {{ $verificationStatuses[$employee->verification_status] ?? $employee->verification_status }}
                    </span>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 col-xl-4">
                <div class="card summary-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <div class="avtar avtar-s {{ $verificationClasses[$employee->verification_status] ?? 'bg-light-secondary text-secondary' }}">
                                <i class="ti ti-user-check f-20"></i>
                            </div>
                            <div>
                                <div class="text-muted small">Status Profil</div>
                                <h6 class="mb-0">{{ $verificationStatuses[$employee->verification_status] ?? $employee->verification_status }}</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="card summary-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <div class="avtar avtar-s bg-light-primary text-primary">
                                <i class="ti ti-files f-20"></i>
                            </div>
                            <div>
                                <div class="text-muted small">Dokumen Saya</div>
                                <h6 class="mb-0">{{ $documentsCount }} dokumen</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="card summary-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <div class="avtar avtar-s {{ $ktpUploaded ? 'bg-light-success text-success' : 'bg-light-warning text-warning' }}">
                                <i class="ti ti-id f-20"></i>
                            </div>
                            <div>
                                <div class="text-muted small">Dokumen KTP</div>
                                <h6 class="mb-0">{{ $ktpUploaded ? 'Sudah diupload' : 'Belum diupload' }}</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="mb-2">Profil Saya</h5>
                        <p class="text-muted mb-3">Lihat dan lengkapi biodata pribadi Anda.</p>
                        <a href="{{ route('pegawai.profile.show') }}" class="btn btn-primary">
                            <i class="ti ti-user"></i>
                            Buka Profil
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="mb-2">Dokumen Saya</h5>
                        <p class="text-muted mb-3">Upload KTP dan dokumen pendukung.</p>
                        <a href="{{ route('pegawai.documents.index') }}" class="btn btn-light-primary">
                            <i class="ti ti-files"></i>
                            Buka Dokumen
                        </a>
                    </div>
                </div>
            </div>

            @if ($idCardRoute)
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="mb-2">ID Card Saya</h5>
                            <p class="text-muted mb-3">Lihat ID Card pegawai setelah data diverifikasi.</p>
                            <a href="{{ $idCardRoute }}" class="btn btn-light-success">
                                <i class="ti ti-id"></i>
                                Buka ID Card
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif
@endsection
