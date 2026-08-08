@php
    $photoUrl = $employee->photo
        ? asset('storage/'.$employee->photo)
        : asset('assets/images/user/avatar-2.jpg');
@endphp

<div class="yapista-id-card">
    <div class="id-card-header">
        <x-application-logo class="id-card-logo" alt="Logo YAPISTA HRIS" />
        <div>
            <div class="id-card-brand">YAPISTA</div>
            <div class="id-card-subtitle">KARTU PEGAWAI</div>
        </div>
    </div>

    <div class="id-card-body">
        <img src="{{ $photoUrl }}" alt="{{ $employee->full_name }}" class="id-card-photo">

        <div class="id-card-name">{{ $employee->full_name }}</div>
        <div class="id-card-number">NUP / Nomor Pegawai: {{ $employee->employee_number }}</div>

        <div class="id-card-info">
            <div>
                <span>Unit Kerja</span>
                <strong>{{ $employee->institution?->name ?? '-' }}</strong>
            </div>
            <div>
                <span>Jabatan</span>
                <strong>{{ $employee->position?->name ?? '-' }}</strong>
            </div>
            <div>
                <span>Status</span>
                <strong>Terverifikasi</strong>
            </div>
        </div>
    </div>

    <div class="id-card-qr">
        @if ($qrCodeSvg)
            <div class="qr-code-svg" role="img" aria-label="QR Code absensi pegawai">{!! $qrCodeSvg !!}</div>
        @else
            <div class="qr-code-placeholder">
                <i class="ti ti-qrcode"></i>
                <span>QR Code belum tersedia</span>
            </div>
        @endif

        <div class="qr-code-label">QR ABSENSI</div>
        <div class="qr-code-note">Pindai QR Code untuk absensi kegiatan.</div>
    </div>
</div>
