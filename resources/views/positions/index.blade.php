@extends('layouts.admin')

@section('title', 'Jabatan | YAPISTA HRIS')

@section('content')
    @php
        $statusBadges = [
            'active' => ['label' => 'Active', 'class' => 'bg-light-success text-success'],
            'inactive' => ['label' => 'Inactive', 'class' => 'bg-light-secondary text-secondary'],
        ];
        $summaryCards = [
            ['label' => 'Total Jabatan', 'value' => $totalPositions ?? $positions->count(), 'icon' => 'ti-briefcase', 'class' => 'bg-light-primary text-primary'],
            ['label' => 'Jabatan Aktif', 'value' => $activePositions ?? $positions->where('status', 'active')->count(), 'icon' => 'ti-circle-check', 'class' => 'bg-light-success text-success'],
            ['label' => 'Jabatan Nonaktif', 'value' => $inactivePositions ?? $positions->where('status', 'inactive')->count(), 'icon' => 'ti-circle-minus', 'class' => 'bg-light-secondary text-secondary'],
            ['label' => 'Unit Tersedia', 'value' => $totalInstitutions ?? $positions->pluck('institution_id')->unique()->count(), 'icon' => 'ti-building', 'class' => 'bg-light-info text-info'],
        ];
    @endphp

    <x-page-header
        title="Jabatan"
        subtitle="Kelola jabatan struktural, fungsional, administratif, dan teknis pada tiap unit kerja."
        :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Master Data'], ['label' => 'Jabatan']]"
    >
        <x-slot:actions>
            @if (auth()->user()->isSuperAdmin())
                <x-import-excel-button target="#importPositionModal" label="Import Jabatan" />
            @endif
            <a href="{{ route('positions.create') }}" class="btn btn-primary"><i class="ti ti-plus" aria-hidden="true"></i> Tambah Jabatan</a>
        </x-slot:actions>
    </x-page-header>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if (session('position_import_summary'))
        @php
            $importSummary = session('position_import_summary');
        @endphp
        <div class="alert alert-success" role="status">
            <div class="fw-semibold mb-2">Import Jabatan selesai.</div>
            <div class="d-flex flex-wrap gap-3 small">
                <span>Berhasil: <strong>{{ $importSummary['created'] }}</strong></span>
                <span>Dilewati: <strong>{{ $importSummary['skipped'] }}</strong></span>
                <span>Gagal: <strong>{{ $importSummary['failed'] }}</strong></span>
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

    @if (auth()->user()->isSuperAdmin())
        <x-import-excel-modal modal-id="importPositionModal" title="Import Data Jabatan"
            :upload-route="route('positions.import.store')" :template-route="route('positions.import.template')"
            :required-columns="['Nama Jabatan', 'Unit Kerja', 'Status']" :optional-columns="['Kategori']"
            accepted-formats="XLSX" accept=".xlsx" max-size="2 MB" submit-label="Import Jabatan" />
    @endif

    <div class="row g-4 mb-4">
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
            <h5 class="mb-0">Filter Jabatan</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('positions.index') }}" class="row g-3 align-items-end">
                <div class="col-lg-5">
                    <label for="search" class="form-label">Cari Jabatan</label>
                    <div class="filter-search-wrap"><i class="ti ti-search" aria-hidden="true"></i><input id="search" type="search" name="search" value="{{ $search }}" class="form-control" placeholder="Cari jabatan, tipe, status, atau unit kerja..." aria-label="Cari jabatan"></div>
                </div>
                <div class="col-lg-3">
                    <label for="institution_id" class="form-label">Unit Kerja</label>
                    <select id="institution_id" name="institution_id" class="form-select">
                        <option value="">Semua Unit Kerja</option>
                        @foreach ($institutions as $institution)
                            <option value="{{ $institution->id }}" @selected($institutionId === $institution->id)>{{ $institution->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-1">
                    <label for="per_page" class="form-label">Tampil</label>
                    <select id="per_page" name="per_page" class="form-select">
                        @foreach ([15, 25, 50] as $size)
                            <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 filter-primary-actions">
                    <button type="submit" class="btn btn-primary flex-fill"><i class="ti ti-filter" aria-hidden="true"></i> Terapkan Filter</button>
                    @if (request()->filled('search') || request()->filled('institution_id') || request('per_page', 15) != 15)<a href="{{ route('positions.index') }}" class="btn btn-light-secondary">Reset</a>@endif
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <h5 class="mb-0">Daftar Jabatan</h5>
                <span class="text-muted small">Menampilkan {{ $positions->firstItem() ?? 0 }}–{{ $positions->lastItem() ?? 0 }} dari {{ $positions->total() }} Jabatan</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4" style="width: 70px;">No</th>
                            <th>Jabatan</th>
                            <th>Unit Kerja</th>
                            <th>Status</th>
                            <th class="text-end pe-4" style="width: 130px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($positions as $position)
                            @php
                                $status = $statusBadges[$position->status] ?? ['label' => $position->status, 'class' => 'bg-light-secondary text-secondary'];
                            @endphp
                            <tr>
                                <td class="ps-4">{{ $positions->firstItem() + $loop->index }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $position->name }}</div>
                                    <div class="data-meta">{{ $position->type ? ucfirst($position->type) : 'Tipe belum diisi' }}</div>
                                </td>
                                <td>
                                    <div>{{ $position->institution?->name ?? '-' }}</div>
                                    <div class="data-meta">{{ $position->institution?->level ?? '-' }}</div>
                                </td>
                                <td>
                                    <span class="badge {{ $status['class'] }}">{{ $status['label'] }}</span>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="table-actions">
                                        <a href="{{ route('positions.edit', $position) }}" class="btn btn-sm btn-light-primary btn-icon" title="Edit">
                                            <i class="ti ti-edit"></i>
                                        </a>

                                        <form action="{{ route('positions.destroy', $position) }}" method="POST" data-confirm-title="Hapus Jabatan?" data-confirm-message="Jabatan yang masih digunakan tidak dapat dihapus. Lanjutkan?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light-danger btn-icon" title="Hapus">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state">
                                        <div class="avtar avtar-l bg-light-secondary text-secondary">
                                            <i class="ti ti-database-off f-28"></i>
                                        </div>
                                        <h5 class="mb-1">{{ request()->filled('search') || request()->filled('institution_id') ? 'Tidak ada jabatan yang sesuai dengan pencarian atau filter.' : 'Belum ada data jabatan.' }}</h5>
                                        <p class="text-muted mb-3">{{ request()->filled('search') || request()->filled('institution_id') ? 'Ubah pencarian atau filter untuk melihat data lainnya.' : 'Silakan tambahkan jabatan terlebih dahulu.' }}</p>
                                        <a href="{{ route('positions.create') }}" class="btn btn-primary">
                                            <i class="ti ti-plus"></i>
                                            Tambah Jabatan
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($positions->hasPages())<div class="card-footer">{{ $positions->links() }}</div>@endif
    </div>
@endsection
