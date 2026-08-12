@props([
    'employee',
    'qrCodeSvg',
])

@php
    $fallbackPhotoUrl = asset('assets/images/user/avatar-2.jpg');
    $photoUrl = $employee->photo
        ? asset('storage/'.$employee->photo)
        : $fallbackPhotoUrl;
    $statusLabel = match ($employee->employment_status) {
        'aktif' => 'Aktif',
        'nonaktif' => 'Nonaktif',
        'resign' => 'Resign',
        default => filled($employee->employment_status)
            ? str($employee->employment_status)->replace('_', ' ')->title()
            : '—',
    };
@endphp

<article {{ $attributes->class(['employee-e-card']) }} aria-label="ID Card digital {{ $employee->full_name }}">
    <div class="employee-e-card-header">
        <x-application-logo
            class="employee-e-card-logo"
            image-class="employee-e-card-logo-image"
            alt="YAPISTA HRIS"
        />
        <span class="employee-e-card-status">
            <span class="employee-e-card-status-dot" aria-hidden="true"></span>
            {{ $statusLabel }}
        </span>
    </div>

    <div class="employee-e-card-content">
        <img
            src="{{ $photoUrl }}"
            alt="Foto {{ $employee->full_name }}"
            class="employee-e-card-photo"
            onerror="this.onerror=null;this.src='{{ $fallbackPhotoUrl }}';"
        >

        <div class="employee-e-card-identity">
            <h2 class="employee-e-card-name">{{ $employee->full_name }}</h2>
            <p class="employee-e-card-position">{{ $employee->position?->name ?? '—' }}</p>
            <p class="employee-e-card-institution">{{ $employee->institution?->name ?? '—' }}</p>

            <div class="employee-e-card-number">
                <span>NUP</span>
                <strong>{{ $employee->employee_number }}</strong>
            </div>
        </div>

        <div class="employee-e-card-qr" role="img" aria-label="QR Code absensi pegawai">
            {!! $qrCodeSvg !!}
        </div>
    </div>

    <p class="employee-e-card-note">Pindai QR Code untuk absensi kegiatan</p>
</article>
