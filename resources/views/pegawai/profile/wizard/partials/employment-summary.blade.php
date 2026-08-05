@php
    $photoUrl = $employee->photo ? asset('storage/'.$employee->photo) : asset('assets/images/user/avatar-2.jpg');
    $employmentLabels = ['aktif' => 'Aktif', 'kontrak' => 'Kontrak', 'honorer' => 'Honorer', 'part_time' => 'Part Time', 'nonaktif' => 'Nonaktif', 'resign' => 'Resign'];
@endphp
<div class="card">
    <div class="card-body text-center">
        <img src="{{ $photoUrl }}" alt="{{ $employee->full_name }}" class="rounded-circle wid-80 hei-80 mb-3" style="object-fit: cover;">
        <h5 class="mb-1">{{ $employee->full_name }}</h5>
        <p class="text-muted mb-3">NUP / Nomor Pegawai: {{ $employee->formatted_employee_number }}</p>
        <div class="text-start border-top pt-3">
            <div class="mb-2"><small class="text-muted d-block">Unit Kerja</small>{{ $employee->institution?->name ?? 'Belum diisi' }}</div>
            <div class="mb-2"><small class="text-muted d-block">Jabatan</small>{{ $employee->position?->name ?? 'Belum diisi' }}</div>
            <div><small class="text-muted d-block">Status Kepegawaian</small>{{ $employmentLabels[$employee->employment_status] ?? $employee->employment_status }}</div>
        </div>
    </div>
</div>
