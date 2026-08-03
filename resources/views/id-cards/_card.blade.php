@php
    $photoUrl = $employee->photo
        ? asset('storage/'.$employee->photo)
        : asset('assets/images/user/avatar-2.jpg');
    $logoUrl = asset('assets/images/logo-yapista-hris.png');
@endphp

<div class="yapista-id-card">
    <div class="id-card-header">
        <img src="{{ $logoUrl }}" alt="YAPISTA HRIS" class="id-card-logo">
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

    <div class="id-card-barcode">
        @if ($barcodeBase64)
            <img src="data:image/png;base64,{{ $barcodeBase64 }}" alt="Barcode {{ $employee->employee_number }}" class="barcode-image">
        @elseif ($barcodeSvg)
            <div class="barcode-svg">{!! $barcodeSvg !!}</div>
        @else
            <div class="barcode-placeholder">
                <i class="ti ti-barcode"></i>
                <span>Barcode belum tersedia</span>
            </div>
        @endif

        <div class="barcode-number">{{ $employee->employee_number }}</div>
        <div class="barcode-note">Scan barcode ini untuk absensi kegiatan.</div>
    </div>
</div>
