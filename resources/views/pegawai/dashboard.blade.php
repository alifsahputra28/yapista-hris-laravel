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
            'submitted' => 'bg-light-warning text-warning',
            'verified' => 'bg-light-success text-success',
            'rejected' => 'bg-light-danger text-danger',
        ];
    @endphp

    <div class="page-header">
        <div class="page-block">
            <div class="row align-items-center">
                <div class="col-md-12">
                    <div class="page-header-title">
                        <h5 class="m-b-10">Dashboard</h5>
                    </div>

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
        <div class="row">
            <div class="col-md-6 col-xl-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="mb-2">Status Verifikasi</h5>
                        <span class="badge {{ $verificationClasses[$employee->verification_status] ?? 'bg-light-secondary text-secondary' }}">
                            {{ $verificationStatuses[$employee->verification_status] ?? $employee->verification_status }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="mb-2">Profil Saya</h5>
                        <p class="text-muted">Lengkapi biodata pribadi Anda.</p>
                        <a href="{{ route('pegawai.profile.show') }}" class="btn btn-primary">
                            <i class="ti ti-user"></i>
                            Buka Profil
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="mb-2">Dokumen Saya</h5>
                        <p class="text-muted">Upload KTP dan dokumen pendukung.</p>
                        <a href="{{ route('pegawai.documents.index') }}" class="btn btn-light-primary">
                            <i class="ti ti-files"></i>
                            Buka Dokumen
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
