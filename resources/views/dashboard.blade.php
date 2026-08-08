@extends('layouts.admin')

@section('title', 'Dashboard Admin | YAPISTA HRIS')

@section('content')
    @php
        $totalEmployees = \App\Models\Employee::query()->count();
        $submittedEmployees = \App\Models\Employee::query()->where('verification_status', 'submitted')->count();
        $registeredEmployees = \App\Models\Employee::query()->whereNotNull('user_id')->count();
        $activeEvents = \App\Models\Event::query()->where('status', 'active')->count();
    @endphp

    <x-page-header
        title="Dashboard"
        subtitle="Ringkasan operasional HRIS YAPISTA."
        :breadcrumbs="[['label' => 'Dashboard']]"
    >
        <x-slot:actions><a href="{{ route('employees.create') }}" class="btn btn-primary"><i class="ti ti-plus" aria-hidden="true"></i> Tambah Pegawai</a></x-slot:actions>
    </x-page-header>

    <div class="metric-strip" aria-label="Ringkasan operasional">
        <div class="metric-item"><div class="metric-label">Total Pegawai</div><div class="metric-value">{{ number_format($totalEmployees) }}</div></div>
        <div class="metric-item"><div class="metric-label">Menunggu Verifikasi</div><div class="metric-value">{{ number_format($submittedEmployees) }}</div></div>
        <div class="metric-item"><div class="metric-label">Akun Terhubung</div><div class="metric-value">{{ number_format($registeredEmployees) }}</div></div>
        <div class="metric-item"><div class="metric-label">Kegiatan Aktif</div><div class="metric-value">{{ number_format($activeEvents) }}</div></div>
    </div>

    <div class="action-list" aria-label="Pekerjaan utama admin">
        <a href="{{ route('employees.index') }}" class="action-list-item"><div><strong class="d-block">Data Pegawai</strong><span class="text-muted small">Cari, tambah, dan kelola data pegawai.</span></div><i class="ti ti-chevron-right" aria-hidden="true"></i></a>
        <a href="{{ route('verifications.index') }}" class="action-list-item"><div><strong class="d-block">Verifikasi Pegawai</strong><span class="text-muted small">{{ $submittedEmployees }} pengajuan menunggu keputusan.</span></div><i class="ti ti-chevron-right" aria-hidden="true"></i></a>
        <a href="{{ route('events.index') }}" class="action-list-item"><div><strong class="d-block">Kegiatan Yayasan</strong><span class="text-muted small">Kelola kegiatan, peserta, dan kehadiran.</span></div><i class="ti ti-chevron-right" aria-hidden="true"></i></a>
        <a href="{{ route('reports.employees') }}" class="action-list-item"><div><strong class="d-block">Laporan</strong><span class="text-muted small">Buka laporan pegawai dan kegiatan.</span></div><i class="ti ti-chevron-right" aria-hidden="true"></i></a>
    </div>
@endsection
