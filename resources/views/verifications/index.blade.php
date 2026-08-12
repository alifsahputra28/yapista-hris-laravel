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
        $activeInstitution = $institutions->firstWhere('id', (int) request('institution_id'))?->name;
        $activePosition = $positions->firstWhere('id', (int) request('position_id'))?->name;
        $advancedFilterCount = collect(['position_id', 'employee_type'])->filter(fn ($key) => request()->filled($key))->count();
        $hasActiveFilters = request()->filled('search') || request()->filled('institution_id') || request()->filled('position_id') || request()->filled('employee_type') || $verificationStatus !== 'submitted';
        $activeFilterCount = collect(['institution_id', 'position_id', 'employee_type'])->filter(fn ($key) => request()->filled($key))->count() + ($verificationStatus !== 'submitted' ? 1 : 0);
    @endphp

    <x-page-header title="Verifikasi Pegawai" subtitle="Periksa pengajuan dan putuskan status pegawai." :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Verifikasi Pegawai']]">
        <x-slot:actions><a href="{{ route('employees.index') }}" class="btn btn-light-secondary"><i class="ti ti-users" aria-hidden="true"></i> Data Pegawai</a></x-slot:actions>
    </x-page-header>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="metric-strip" aria-label="Ringkasan verifikasi">
        <div class="metric-item"><div class="metric-label">Menunggu</div><div class="metric-value">{{ number_format($submittedEmployees ?? 0) }}</div></div>
        <div class="metric-item"><div class="metric-label">Terverifikasi</div><div class="metric-value">{{ number_format($verifiedEmployees ?? 0) }}</div></div>
        <div class="metric-item"><div class="metric-label">Ditolak</div><div class="metric-value">{{ number_format($rejectedEmployees ?? 0) }}</div></div>
        <div class="metric-item"><div class="metric-label">Draft</div><div class="metric-value">{{ number_format($draftEmployees ?? 0) }}</div></div>
    </div>

    <div class="card filter-card">
        <div class="card-header">
            <h5 class="mb-0">Filter Verifikasi</h5>
        </div>
        <div class="card-body">
            <form id="verification-filter-form" method="GET" action="{{ route('verifications.index') }}">
                <div class="row g-3 align-items-end">
                <div class="col-lg-5">
                    <label for="search" class="form-label">Cari Pegawai</label>
                    <div class="filter-search-wrap"><i class="ti ti-search" aria-hidden="true"></i><input id="search" type="search" name="search" value="{{ $search }}" class="form-control" placeholder="Cari nama, email, HP, atau NUP..." aria-label="Cari pegawai untuk diverifikasi"></div>
                </div>
                <div class="col-12 d-lg-none"><button class="btn btn-light-primary w-100" type="button" data-bs-toggle="collapse" data-bs-target=".verification-mobile-filter" aria-expanded="{{ $activeFilterCount ? 'true' : 'false' }}"><i class="ti ti-adjustments-horizontal" aria-hidden="true"></i> Filter @if ($activeFilterCount)({{ $activeFilterCount }})@endif</button></div>
                <div class="col-md-6 col-lg-3 collapse d-lg-block verification-mobile-filter {{ $activeFilterCount ? 'show' : '' }}">
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

                <div class="col-md-6 col-lg-3 collapse d-lg-block verification-mobile-filter {{ $activeFilterCount ? 'show' : '' }}">
                    <label for="verification_status" class="form-label">Status</label>
                    <select id="verification_status" name="verification_status" class="form-select">
                        @foreach (['submitted', 'verified', 'rejected'] as $status)
                            <option value="{{ $status }}" @selected($verificationStatus === $status)>
                                {{ $verificationStatuses[$status]['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-1 filter-primary-actions collapse d-lg-block verification-mobile-filter {{ $activeFilterCount ? 'show' : '' }}"><button type="submit" class="btn btn-primary w-100" title="Terapkan Filter"><i class="ti ti-filter" aria-hidden="true"></i><span class="d-lg-none">Terapkan Filter</span></button></div>
                </div>
            </form>

            <div class="filter-secondary-row collapse d-lg-flex verification-mobile-filter {{ $activeFilterCount ? 'show' : '' }}">
                <button class="btn btn-link filter-advanced-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#verification-advanced-filter" aria-expanded="{{ $advancedFilterCount ? 'true' : 'false' }}" aria-controls="verification-advanced-filter"><i class="ti ti-adjustments-horizontal" aria-hidden="true"></i>Filter Lanjutan @if ($advancedFilterCount)<span class="badge bg-light-primary text-primary">{{ $advancedFilterCount }}</span>@endif<i class="ti ti-chevron-down" aria-hidden="true"></i></button>
                @if ($hasActiveFilters)<a href="{{ route('verifications.index') }}" class="btn btn-sm btn-link text-muted">Reset semua</a>@endif
            </div>
            <div id="verification-advanced-filter" class="collapse {{ $advancedFilterCount ? 'show' : '' }}">
                <div class="filter-advanced-panel"><div class="row g-3">
                    <div class="col-md-6"><label for="position_id" class="form-label">Jabatan</label><select id="position_id" name="position_id" class="form-select" form="verification-filter-form"><option value="">Semua jabatan</option>@foreach ($positions as $position)<option value="{{ $position->id }}" @selected((string) request('position_id') === (string) $position->id)>{{ $position->name }}</option>@endforeach</select></div>
                    <div class="col-md-6"><label for="employee_type" class="form-label">Jenis Pegawai</label><select id="employee_type" name="employee_type" class="form-select" form="verification-filter-form"><option value="">Semua jenis</option>@foreach ($employeeTypes as $value => $label)<option value="{{ $value }}" @selected(request('employee_type') === $value)>{{ $label }}</option>@endforeach</select></div>
                </div></div>
            </div>
            @if ($hasActiveFilters)
                <div class="active-filter-summary" aria-label="Filter aktif"><span class="active-filter-label">Filter aktif:</span>
                    @if ($activeInstitution)<x-active-filter-chip label="Unit" :value="$activeInstitution" :url="route('verifications.index', request()->except('institution_id', 'page'))" />@endif
                    @if ($verificationStatus !== 'submitted')<x-active-filter-chip label="Status" :value="$verificationStatuses[$verificationStatus]['label']" :url="route('verifications.index', request()->except('verification_status', 'page'))" />@endif
                    @if ($activePosition)<x-active-filter-chip label="Jabatan" :value="$activePosition" :url="route('verifications.index', request()->except('position_id', 'page'))" />@endif
                    @if (request('employee_type'))<x-active-filter-chip label="Jenis" :value="$employeeTypes[request('employee_type')] ?? request('employee_type')" :url="route('verifications.index', request()->except('employee_type', 'page'))" />@endif
                </div>
            @endif
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
                                    <div class="data-meta">NUP / Nomor Pegawai: {{ $employee->employee_number ?? 'Belum dibuat' }}</div>
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
                                        <h5 class="mb-1">{{ $hasActiveFilters ? 'Tidak ada pegawai yang sesuai dengan filter.' : 'Belum ada data pegawai yang menunggu verifikasi.' }}</h5>
                                        <p class="text-muted mb-3">{{ $hasActiveFilters ? 'Ubah atau reset filter untuk melihat pengajuan lainnya.' : 'Data akan muncul setelah pegawai mengajukan verifikasi profil.' }}</p>
                                        @if ($hasActiveFilters)<a href="{{ route('verifications.index') }}" class="btn btn-light-primary"><i class="ti ti-filter-off"></i> Reset Filter</a>@endif
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
