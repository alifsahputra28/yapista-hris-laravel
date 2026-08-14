@extends('layouts.admin')

@section('title', 'Data Pegawai | YAPISTA HRIS')

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
        $employmentStatuses = [
            'aktif' => ['label' => 'Aktif', 'class' => 'bg-light-success text-success'],
            'kontrak' => ['label' => 'Kontrak', 'class' => 'bg-light-primary text-primary'],
            'honorer' => ['label' => 'Honorer', 'class' => 'bg-light-warning text-warning'],
            'part_time' => ['label' => 'Part Time', 'class' => 'bg-light-info text-info'],
            'nonaktif' => ['label' => 'Nonaktif', 'class' => 'bg-light-danger text-danger'],
            'resign' => ['label' => 'Resign', 'class' => 'bg-light-secondary text-secondary'],
        ];
        $verificationStatuses = [
            'draft' => ['label' => 'Draft', 'class' => 'bg-light-secondary text-secondary'],
            'submitted' => ['label' => 'Menunggu Verifikasi', 'class' => 'bg-light-primary text-primary'],
            'verified' => ['label' => 'Terverifikasi', 'class' => 'bg-light-success text-success'],
            'rejected' => ['label' => 'Ditolak', 'class' => 'bg-light-danger text-danger'],
        ];
        $activeInstitution = $institutions->firstWhere('id', (int) request('institution_id'))?->name;
        $activePosition = $positions->firstWhere('id', (int) request('position_id'))?->name;
        $advancedFilterCount = collect(['verification_status', 'employee_type'])->filter(fn ($key) => request()->filled($key))->count();
        $hasActiveFilters = collect(['search', 'institution_id', 'position_id', 'employment_status', 'verification_status', 'employee_type'])
            ->contains(fn ($key) => request()->filled($key));
        $activeFilterCount = collect(['institution_id', 'position_id', 'employment_status', 'verification_status', 'employee_type'])->filter(fn ($key) => request()->filled($key))->count();
        $advancedOpen = $advancedFilterCount > 0 || session()->has('error');
    @endphp

    <x-page-header title="Data Pegawai" subtitle="Cari dan kelola data pegawai YAPISTA." :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Data Pegawai']]">
        <x-slot:actions>
            <x-import-excel-button target="#importEmployeeModal" />
            <a href="{{ route('employees.create') }}" class="btn btn-primary"><i class="ti ti-plus" aria-hidden="true"></i> Tambah Pegawai</a>
        </x-slot:actions>
    </x-page-header>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if (session('import_summary'))
        @php
            $importSummary = session('import_summary');
        @endphp
        <div class="alert alert-success" role="status">
            <div class="fw-semibold mb-2">Import selesai.</div>
            <div class="d-flex flex-wrap gap-3 small">
                <span>Berhasil: <strong>{{ $importSummary['created'] }}</strong></span>
                <span>Dilewati: <strong>{{ $importSummary['skipped'] }}</strong></span>
                <span>Gagal: <strong>{{ $importSummary['failed'] }}</strong></span>
                <span>QR dibuat: <strong>{{ $importSummary['qr_tokens_created'] }}</strong></span>
            </div>
            @if ($importSummary['errors'])
                <details class="mt-2">
                    <summary class="small fw-medium">Lihat catatan baris</summary>
                    <ul class="small mb-0 mt-2 ps-3">
                        @foreach ($importSummary['errors'] as $importError)
                            <li>{{ $importError }}</li>
                        @endforeach
                    </ul>
                </details>
            @endif
        </div>
    @endif

    <x-import-excel-modal
        modal-id="importEmployeeModal"
        title="Import Excel Data Pegawai"
        :upload-route="route('employees.import.store')"
        :template-route="route('employees.import.template')"
        :required-columns="$importRequiredColumns"
        :optional-columns="$importOptionalColumns"
    />

    <div class="metric-strip" aria-label="Ringkasan pegawai">
        <div class="metric-item"><div class="metric-label">Total Pegawai</div><div class="metric-value">{{ number_format($totalEmployees) }}</div></div>
        <div class="metric-item"><div class="metric-label">Pegawai Aktif</div><div class="metric-value">{{ number_format($activeEmployees) }}</div></div>
        <div class="metric-item"><div class="metric-label">Menunggu Verifikasi</div><div class="metric-value">{{ number_format($submittedEmployees) }}</div></div>
        <div class="metric-item"><div class="metric-label">Akun Terhubung</div><div class="metric-value">{{ number_format($registeredEmployees) }}</div></div>
    </div>

    <div class="card filter-card">
        <div class="card-header">
            <h5 class="mb-0">Filter Pegawai</h5>
        </div>
        <div class="card-body">
            <form id="employee-filter-form" method="GET" action="{{ route('employees.index') }}">
                <div class="row g-3 align-items-end">
                <div class="col-lg-5">
                    <label for="search" class="form-label">Cari Pegawai</label>
                    <div class="filter-search-wrap">
                        <i class="ti ti-search" aria-hidden="true"></i>
                        <input id="search" type="search" name="search" value="{{ $search }}" class="form-control" placeholder="Cari nama, email, HP, atau NUP..." aria-label="Cari nama, email, HP, atau NUP">
                    </div>
                </div>
                <div class="col-12 d-lg-none"><button class="btn btn-light-primary w-100" type="button" data-bs-toggle="collapse" data-bs-target=".employee-mobile-filter" aria-expanded="{{ $activeFilterCount ? 'true' : 'false' }}"><i class="ti ti-adjustments-horizontal" aria-hidden="true"></i> Filter @if ($activeFilterCount)({{ $activeFilterCount }})@endif</button></div>
                <div class="col-md-6 col-lg-2 collapse d-lg-block employee-mobile-filter {{ $activeFilterCount ? 'show' : '' }}">
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

                <div class="col-md-6 col-lg-2 collapse d-lg-block employee-mobile-filter {{ $activeFilterCount ? 'show' : '' }}">
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

                <div class="col-md-6 col-lg-2 collapse d-lg-block employee-mobile-filter {{ $activeFilterCount ? 'show' : '' }}">
                    <label for="employment_status" class="form-label">Status Kerja</label>
                    <select id="employment_status" name="employment_status" class="form-select">
                        <option value="">Semua status</option>
                        @foreach ($employmentStatuses as $value => $status)
                            <option value="{{ $value }}" @selected(request('employment_status') === $value)>
                                {{ $status['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-1 filter-primary-actions collapse d-lg-block employee-mobile-filter {{ $activeFilterCount ? 'show' : '' }}">
                    <button type="submit" class="btn btn-primary w-100" title="Terapkan Filter">
                        <i class="ti ti-filter" aria-hidden="true"></i>
                        <span class="d-lg-none">Terapkan Filter</span>
                    </button>
                </div>
                </div>
            </form>

            <div class="filter-secondary-row collapse d-lg-flex employee-mobile-filter {{ $activeFilterCount ? 'show' : '' }}">
                <button class="btn btn-link filter-advanced-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#employee-advanced-filter" aria-expanded="{{ $advancedOpen ? 'true' : 'false' }}" aria-controls="employee-advanced-filter">
                    <i class="ti ti-adjustments-horizontal" aria-hidden="true"></i>
                    Filter Lanjutan @if ($advancedFilterCount)<span class="badge bg-light-primary text-primary">{{ $advancedFilterCount }}</span>@endif
                    <i class="ti ti-chevron-down" aria-hidden="true"></i>
                </button>
                @if ($hasActiveFilters)
                    <a href="{{ route('employees.index') }}" class="btn btn-sm btn-link text-muted">Reset semua</a>
                @endif
            </div>

            <div id="employee-advanced-filter" class="collapse {{ $advancedOpen ? 'show' : '' }}">
                <div class="filter-advanced-panel">
                    <div class="row g-3">
                        <div class="col-md-6 col-lg-3">
                            <label for="verification_status" class="form-label">Status Verifikasi</label>
                            <select id="verification_status" name="verification_status" class="form-select" form="employee-filter-form">
                                <option value="">Semua verifikasi</option>
                                @foreach ($verificationStatuses as $value => $status)
                                    <option value="{{ $value }}" @selected(request('verification_status') === $value)>{{ $status['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <label for="employee_type" class="form-label">Jenis Pegawai</label>
                            <select id="employee_type" name="employee_type" class="form-select" form="employee-filter-form">
                                <option value="">Semua jenis</option>
                                @foreach ($employeeTypes as $value => $label)
                                    <option value="{{ $value }}" @selected(request('employee_type') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12"><hr class="my-1"></div>
                        <div class="col-12">
                            <form method="POST" action="{{ route('employees.nik-search') }}" class="row g-3 align-items-end" autocomplete="off">
                                @csrf
                                <div class="col-lg-7">
                                    <label for="nik_exact" class="form-label">Cari berdasarkan NIK</label>
                                    <input id="nik_exact" type="text" name="nik" class="form-control" maxlength="16" inputmode="numeric" autocomplete="off" placeholder="Masukkan 16 digit NIK">
                                    <div class="form-text">Pencarian NIK menggunakan exact secure lookup dan tidak disimpan pada URL.</div>
                                </div>
                                <div class="col-lg-auto"><button type="submit" class="btn btn-outline-primary"><i class="ti ti-search" aria-hidden="true"></i> Cari NIK</button></div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            @if ($hasActiveFilters)
                <div class="active-filter-summary" aria-label="Filter aktif">
                    <span class="active-filter-label">Filter aktif:</span>
                    @if ($activeInstitution)<x-active-filter-chip label="Unit" :value="$activeInstitution" :url="route('employees.index', request()->except('institution_id', 'page'))" />@endif
                    @if ($activePosition)<x-active-filter-chip label="Jabatan" :value="$activePosition" :url="route('employees.index', request()->except('position_id', 'page'))" />@endif
                    @if (request('employment_status'))<x-active-filter-chip label="Status" :value="$employmentStatuses[request('employment_status')]['label'] ?? request('employment_status')" :url="route('employees.index', request()->except('employment_status', 'page'))" />@endif
                    @if (request('verification_status'))<x-active-filter-chip label="Verifikasi" :value="$verificationStatuses[request('verification_status')]['label'] ?? request('verification_status')" :url="route('employees.index', request()->except('verification_status', 'page'))" />@endif
                    @if (request('employee_type'))<x-active-filter-chip label="Jenis" :value="$employeeTypes[request('employee_type')] ?? request('employee_type')" :url="route('employees.index', request()->except('employee_type', 'page'))" />@endif
                </div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <h5 class="mb-0">Daftar Pegawai</h5>
                <span class="text-muted small">{{ $employees->total() }} data ditampilkan berdasarkan filter saat ini</span>
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
                            <th>Registrasi</th>
                            <th class="text-end pe-4" style="width: 170px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($employees as $employee)
                            @php
                                $employmentStatus = $employmentStatuses[$employee->employment_status] ?? [
                                    'label' => $employee->employment_status ?? '-',
                                    'class' => 'bg-light-secondary text-secondary',
                                ];
                                $verificationStatus = $verificationStatuses[$employee->verification_status] ?? [
                                    'label' => $employee->verification_status ?? '-',
                                    'class' => 'bg-light-secondary text-secondary',
                                ];
                                $idCardRoute = Route::has('employees.id-card.show')
                                    ? route('employees.id-card.show', $employee)
                                    : null;
                            @endphp

                            <tr>
                                <td class="ps-4">{{ $employees->firstItem() + $loop->index }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $employee->full_name }}</div>
                                    <div class="small mt-1">
                                        @if ($employee->employee_number)
                                            <span class="text-muted">NUP / Nomor Pegawai: {{ $employee->employee_number }}</span>
                                        @else
                                            <span class="badge bg-light-secondary text-secondary">Belum diisi</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-medium">{{ $employee->institution?->name ?? '-' }}</div>
                                    <div class="text-muted small">{{ $employee->position?->name ?? '-' }}</div>
                                    <div class="text-muted small">Jenis: {{ $employeeTypes[$employee->employee_type] ?? $employee->employee_type ?? '-' }}</div>
                                </td>
                                <td>
                                    <div>{{ $employee->email ?? '-' }}</div>
                                    <div class="text-muted small">{{ $employee->phone ?? '-' }}</div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column align-items-start gap-1">
                                        <span class="badge {{ $employmentStatus['class'] }}">{{ $employmentStatus['label'] }}</span>
                                        <span class="badge {{ $verificationStatus['class'] }}">{{ $verificationStatus['label'] }}</span>
                                    </div>
                                </td>
                                <td>
                                    @if ($employee->user_id)
                                        <span class="badge bg-light-success text-success">Sudah Registrasi</span>
                                    @else
                                        <span class="badge bg-light-warning text-warning">Belum Registrasi</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <div class="table-actions">
                                        <a href="{{ route('employees.show', $employee) }}" class="btn btn-sm btn-light-secondary">
                                            <i class="ti ti-eye"></i>
                                            Detail
                                        </a>

                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-light-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                Aksi
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <a class="dropdown-item" href="{{ route('employees.edit', $employee) }}">
                                                    <i class="ti ti-edit me-2"></i>
                                                    Edit
                                                </a>

                                                @if ($employee->user_id === null)
                                                    <form action="{{ route('employees.invitations.generate', $employee) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item">
                                                            <i class="ti ti-mail me-2"></i>
                                                            Buat Undangan
                                                        </button>
                                                    </form>
                                                @endif

                                                @if ($idCardRoute)
                                                    <a class="dropdown-item" href="{{ $idCardRoute }}">
                                                        <i class="ti ti-id me-2"></i>
                                                        Lihat ID Card
                                                    </a>
                                                @endif

                                                @if ($employee->isSubmitted())
                                                    <a class="dropdown-item" href="{{ route('verifications.show', $employee) }}">
                                                        <i class="ti ti-user-check me-2"></i>
                                                        Detail Verifikasi
                                                    </a>
                                                @endif

                                                <div class="dropdown-divider"></div>

                                                <form action="{{ route('employees.destroy', $employee) }}" method="POST" data-confirm-title="Nonaktifkan Pegawai?" data-confirm-message="Pegawai akan dinonaktifkan dan tidak lagi berstatus aktif. Lanjutkan?">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger" @disabled($employee->employment_status === 'nonaktif')>
                                                        <i class="ti ti-user-off me-2"></i>
                                                        Nonaktifkan
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="text-center py-5">
                                        <div class="avtar avtar-l bg-light-secondary text-secondary mx-auto mb-3">
                                            <i class="ti ti-users f-28"></i>
                                        </div>
                                        <h5 class="mb-1">{{ $hasActiveFilters ? 'Tidak ada pegawai yang sesuai dengan filter.' : 'Belum ada data pegawai.' }}</h5>
                                        <p class="text-muted mb-3">{{ $hasActiveFilters ? 'Ubah atau reset filter untuk melihat data lainnya.' : 'Silakan tambahkan pegawai baru terlebih dahulu.' }}</p>
                                        <a href="{{ $hasActiveFilters ? route('employees.index') : route('employees.create') }}" class="btn btn-primary">
                                            <i class="ti {{ $hasActiveFilters ? 'ti-filter-off' : 'ti-plus' }}"></i>
                                            {{ $hasActiveFilters ? 'Reset Filter' : 'Tambah Pegawai' }}
                                        </a>
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
