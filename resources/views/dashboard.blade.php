@extends('layouts.admin')

@section('title', 'Dashboard Admin | YAPISTA HRIS')

@section('content')
    @php
        $employeeModel = class_exists(\App\Models\Employee::class) ? \App\Models\Employee::class : null;
        $eventModel = class_exists(\App\Models\Event::class) ? \App\Models\Event::class : null;

        $totalEmployees = $employeeModel ? $employeeModel::query()->count() : 0;
        $submittedEmployees = $employeeModel ? $employeeModel::query()->where('verification_status', 'submitted')->count() : 0;
        $registeredEmployees = $employeeModel ? $employeeModel::query()->whereNotNull('user_id')->count() : 0;
        $activeEvents = $eventModel ? $eventModel::query()->where('status', 'active')->count() : 0;

        $summaryCards = [
            ['label' => 'Total Pegawai', 'value' => $totalEmployees, 'icon' => 'ti-users', 'class' => 'bg-light-primary text-primary'],
            ['label' => 'Menunggu Verifikasi', 'value' => $submittedEmployees, 'icon' => 'ti-user-check', 'class' => 'bg-light-warning text-warning'],
            ['label' => 'Sudah Registrasi', 'value' => $registeredEmployees, 'icon' => 'ti-user-shield', 'class' => 'bg-light-success text-success'],
            ['label' => 'Kegiatan Aktif', 'value' => $activeEvents, 'icon' => 'ti-calendar-event', 'class' => 'bg-light-info text-info'],
        ];
    @endphp

    <div class="page-header">
        <div class="page-block">
            <div class="row align-items-center">
                <div class="col-md-12">
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="{{ route('dashboard') }}">Home</a>
                        </li>
                        <li class="breadcrumb-item" aria-current="page">Dashboard Admin</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="card page-intro-card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <h4 class="mb-1">Dashboard Admin HRIS YAPISTA</h4>
                    <p class="mb-0 text-muted">Pantau data pegawai, verifikasi, registrasi, dan kegiatan yayasan.</p>
                </div>

                <a href="{{ route('employees.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus"></i>
                    Tambah Pegawai
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        @foreach ($summaryCards as $card)
            <div class="col-md-6 col-xl-3">
                <div class="card summary-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <div class="avtar avtar-s {{ $card['class'] }}">
                                <i class="ti {{ $card['icon'] }} f-20"></i>
                            </div>
                            <div>
                                <div class="text-muted small">{{ $card['label'] }}</div>
                                <h4 class="mb-0">{{ number_format($card['value']) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-2">Data Pegawai</h5>
                    <p class="text-muted mb-3">Kelola data dasar pegawai dan status registrasi akun.</p>
                    <a href="{{ route('employees.index') }}" class="btn btn-light-primary">
                        <i class="ti ti-users"></i>
                        Buka Data Pegawai
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-2">Verifikasi Pegawai</h5>
                    <p class="text-muted mb-3">Periksa biodata dan dokumen pegawai yang diajukan.</p>
                    <a href="{{ route('verifications.index') }}" class="btn btn-light-success">
                        <i class="ti ti-user-check"></i>
                        Buka Verifikasi
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-2">Kegiatan Yayasan</h5>
                    <p class="text-muted mb-3">Kelola kegiatan dan peserta yang sudah terverifikasi.</p>
                    <a href="{{ route('events.index') }}" class="btn btn-light-info">
                        <i class="ti ti-calendar-event"></i>
                        Buka Kegiatan
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
