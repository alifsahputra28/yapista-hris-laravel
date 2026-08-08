@extends('layouts.admin')

@section('title', 'Dashboard Pegawai | YAPISTA HRIS')

@section('content')
    @php
        $employee = Auth::user()?->employee;
        $verificationStatuses = ['draft' => 'Draft', 'submitted' => 'Menunggu Verifikasi', 'verified' => 'Terverifikasi', 'rejected' => 'Perlu Perbaikan'];
        $verificationClasses = ['draft' => 'bg-light-secondary text-secondary', 'submitted' => 'bg-light-warning text-warning', 'verified' => 'bg-light-success text-success', 'rejected' => 'bg-light-danger text-danger'];
        $documentsCount = $employee ? $employee->documents()->count() : 0;
    @endphp

    @if (! $employee)
        <x-page-header title="Dashboard" subtitle="Akun pegawai YAPISTA." :breadcrumbs="[['label' => 'Dashboard']]" />
        <div class="alert alert-warning">Data pegawai belum terhubung dengan akun Anda. Hubungi HR/Admin.</div>
    @else
        <x-page-header
            title="Dashboard"
            :subtitle="$employee->isVerified() ? 'Akses cepat ke profil, dokumen, dan ID Card Anda.' : 'Selesaikan tindakan yang diperlukan untuk proses verifikasi.'"
            :badge-label="$verificationStatuses[$employee->verification_status] ?? $employee->verification_status"
            :badge-class="$verificationClasses[$employee->verification_status] ?? 'bg-light-secondary text-secondary'"
            :breadcrumbs="[['label' => 'Dashboard']]"
        >
            <x-slot:meta><x-employee-context :employee="$employee" /></x-slot:meta>
        </x-page-header>

        @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
        @if ($employee->verification_status === 'rejected' && filled($employee->verification_note))
            <div class="alert alert-danger"><strong>Perlu perbaikan:</strong> {{ $employee->verification_note }}</div>
        @elseif ($employee->isSubmitted())
            <div class="alert alert-warning">Data sedang diperiksa HR. Perubahan profil sementara dikunci.</div>
        @endif

        <div class="action-list" aria-label="Menu utama pegawai">
            <a href="{{ route('pegawai.profile.show') }}" class="action-list-item">
                <div><strong class="d-block">Profil Saya</strong><span class="text-muted small">Lihat identitas dan data administrasi.</span></div>
                <i class="ti ti-chevron-right" aria-hidden="true"></i>
            </a>
            <a href="{{ route('pegawai.documents.index') }}" class="action-list-item">
                <div><strong class="d-block">Dokumen Saya</strong><span class="text-muted small">{{ $documentsCount }} dokumen tersimpan.</span></div>
                <i class="ti ti-chevron-right" aria-hidden="true"></i>
            </a>
            @if (Route::has('pegawai.id-card.show'))
                <a href="{{ route('pegawai.id-card.show') }}" class="action-list-item">
                    <div><strong class="d-block">ID Card Saya</strong><span class="text-muted small">{{ $employee->isEligibleForIdCard() ? 'Kartu siap dilihat.' : 'Tersedia setelah verifikasi dan NUP valid.' }}</span></div>
                    <i class="ti ti-chevron-right" aria-hidden="true"></i>
                </a>
            @endif
        </div>
    @endif
@endsection
