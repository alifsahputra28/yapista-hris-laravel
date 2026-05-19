@extends('layouts.admin')

@section('title', 'Verifikasi Pegawai | YAPISTA HRIS')

@section('content')
    @php
        $employeeTypes = [
            'guru' => 'Guru',
            'dosen' => 'Dosen',
            'tenaga_kependidikan' => 'Tenaga Kependidikan',
            'staff_yayasan' => 'Staff Yayasan',
            'security' => 'Security',
            'cleaning_service' => 'Cleaning Service',
            'driver' => 'Driver',
            'teknisi' => 'Teknisi',
        ];
        $verificationStatuses = [
            'draft' => ['label' => 'Draft', 'class' => 'bg-light-secondary text-secondary'],
            'submitted' => ['label' => 'Menunggu Verifikasi', 'class' => 'bg-light-primary text-primary'],
            'verified' => ['label' => 'Terverifikasi', 'class' => 'bg-light-success text-success'],
            'rejected' => ['label' => 'Ditolak', 'class' => 'bg-light-danger text-danger'],
        ];
        $summaryCards = [
            ['label' => 'Menunggu Verifikasi', 'value' => $submittedEmployees ?? 0, 'icon' => 'ti-user-check', 'class' => 'bg-light-primary text-primary'],
            ['label' => 'Terverifikasi', 'value' => $verifiedEmployees ?? 0, 'icon' => 'ti-circle-check', 'class' => 'bg-light-success text-success'],
            ['label' => 'Ditolak', 'value' => $rejectedEmployees ?? 0, 'icon' => 'ti-circle-x', 'class' => 'bg-light-danger text-danger'],
            ['label' => 'Draft', 'value' => $draftEmployees ?? 0, 'icon' => 'ti-file-pencil', 'class' => 'bg-light-secondary text-secondary'],
        ];
    @endphp

    <div class="page-header">
        <div class="page-block">
            <div class="row align-items-center">
                <div class="col-md-12">
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item" aria-current="page">Verifikasi Pegawai</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card page-intro-card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <h4 class="mb-1">Verifikasi Pegawai</h4>
                    <p class="mb-0 text-muted">Periksa biodata, foto, unit kerja, jabatan, dan dokumen pegawai sebelum approve.</p>
                </div>

                <a href="{{ route('employees.index') }}" class="btn btn-light-secondary">
                    <i class="ti ti-users"></i>
                    Data Pegawai
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

    <div class="card filter-card">
        <div class="card-header">
            <h5 class="mb-0">Filter Verifikasi</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('verifications.index') }}" class="row g-3">
                <div class="col-lg-4">
                    <label for="search" class="form-label">Pencarian</label>
                    <input id="search" type="search" name="search" value="{{ $search }}" class="form-control" placeholder="Cari nama, email, HP, NIK, atau nomor">
                </div>

                <div class="col-md-6 col-lg-2">
                    <label for="institution_id" class="form-label">Unit Kerja</label>
                    <select id="institution_id" name="institution_id" class="form-select">
                        <option value="">Semua unit</option>
                        @foreach ($institutions as $institution)
                            <option value="{{ $institution->id }}" @selected((string) request('institution_id') === (string) $institution->id)>
                                {{ $institution->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 col-lg-2">
                    <label for="position_id" class="form-label">Jabatan</label>
                    <select id="position_id" name="position_id" class="form-select">
                        <option value="">Semua jabatan</option>
                        @foreach ($positions as $position)
                            <option value="{{ $position->id }}" @selected((string) request('position_id') === (string) $position->id)>
                                {{ $position->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 col-lg-2">
                    <label for="employee_type" class="form-label">Jenis Pegawai</label>
                    <select id="employee_type" name="employee_type" class="form-select">
                        <option value="">Semua jenis</option>
                        @foreach ($employeeTypes as $value => $label)
                            <option value="{{ $value }}" @selected(request('employee_type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 col-lg-2">
                    <label for="verification_status" class="form-label">Status</label>
                    <select id="verification_status" name="verification_status" class="form-select">
                        @foreach (['submitted', 'verified', 'rejected'] as $status)
                            <option value="{{ $status }}" @selected($verificationStatus === $status)>
                                {{ $verificationStatuses[$status]['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-filter"></i>
                        Filter
                    </button>
                    <a href="{{ route('verifications.index') }}" class="btn btn-light-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <h5 class="mb-0">Daftar Pegawai</h5>
                <span class="text-muted small">{{ $employees->total() }} data berdasarkan filter saat ini</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4" style="width: 70px;">No</th>
                            <th>Pegawai</th>
                            <th>Unit & Jabatan</th>
                            <th>Kontak</th>
                            <th>Status</th>
                            <th>Dokumen</th>
                            <th>Diajukan</th>
                            <th class="text-end pe-4" style="width: 140px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($employees as $employee)
                            @php
                                $status = $verificationStatuses[$employee->verification_status] ?? ['label' => $employee->verification_status, 'class' => 'bg-light-secondary text-secondary'];
                            @endphp
                            <tr>
                                <td class="ps-4">{{ $employees->firstItem() + $loop->index }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $employee->full_name }}</div>
                                    <div class="data-meta">{{ $employee->employee_number ?? 'Belum dibuat' }}</div>
                                </td>
                                <td>
                                    <div>{{ $employee->institution?->name ?? '-' }}</div>
                                    <div class="data-meta">{{ $employee->position?->name ?? '-' }}</div>
                                </td>
                                <td>
                                    <div>{{ $employee->email ?? '-' }}</div>
                                    <div class="data-meta">{{ $employee->phone ?? '-' }}</div>
                                </td>
                                <td>
                                    <span class="badge {{ $status['class'] }}">{{ $status['label'] }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-light-info text-info">{{ $employee->documents->count() }} dokumen</span>
                                </td>
                                <td>{{ $employee->updated_at?->format('d M Y H:i') ?? '-' }}</td>
                                <td class="text-end pe-4">
                                    <div class="table-actions">
                                        <a href="{{ route('verifications.show', $employee) }}" class="btn btn-sm btn-primary">
                                            <i class="ti ti-user-check"></i>
                                            Detail
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state">
                                        <div class="avtar avtar-l bg-light-secondary text-secondary">
                                            <i class="ti ti-user-check f-28"></i>
                                        </div>
                                        <h5 class="mb-1">Belum ada data pegawai yang menunggu verifikasi.</h5>
                                        <p class="text-muted mb-0">Data akan muncul setelah pegawai mengajukan verifikasi profil.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($employees->hasPages())
            <div class="card-footer">
                {{ $employees->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
@endsection
