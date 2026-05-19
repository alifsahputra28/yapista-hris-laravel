@extends('layouts.admin')

@section('title', 'Unit Kerja | YAPISTA HRIS')

@section('content')
    @php
        $statusBadges = [
            'active' => ['label' => 'Active', 'class' => 'bg-light-success text-success'],
            'inactive' => ['label' => 'Inactive', 'class' => 'bg-light-secondary text-secondary'],
        ];
        $summaryCards = [
            ['label' => 'Total Unit', 'value' => $totalInstitutions ?? $institutions->count(), 'icon' => 'ti-building', 'class' => 'bg-light-primary text-primary'],
            ['label' => 'Unit Aktif', 'value' => $activeInstitutions ?? $institutions->where('status', 'active')->count(), 'icon' => 'ti-circle-check', 'class' => 'bg-light-success text-success'],
            ['label' => 'Unit Nonaktif', 'value' => $inactiveInstitutions ?? $institutions->where('status', 'inactive')->count(), 'icon' => 'ti-circle-minus', 'class' => 'bg-light-secondary text-secondary'],
            ['label' => 'Total Jabatan', 'value' => $totalPositions ?? $institutions->sum('positions_count'), 'icon' => 'ti-briefcase', 'class' => 'bg-light-info text-info'],
        ];
    @endphp

    <div class="page-header">
        <div class="page-block">
            <div class="row align-items-center">
                <div class="col-md-12">
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item" aria-current="page">Master Data</li>
                        <li class="breadcrumb-item" aria-current="page">Unit Kerja</li>
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
                    <h4 class="mb-1">Unit Kerja</h4>
                    <p class="mb-0 text-muted">Kelola master data lembaga, sekolah, perguruan tinggi, dan kantor yayasan.</p>
                </div>

                <a href="{{ route('institutions.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus"></i>
                    Tambah Unit Kerja
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
            <h5 class="mb-0">Filter Unit Kerja</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('institutions.index') }}" class="row g-3">
                <div class="col-lg-8">
                    <label for="search" class="form-label">Pencarian</label>
                    <input id="search" type="search" name="search" value="{{ $search }}" class="form-control" placeholder="Cari nama, level, alamat, atau status">
                </div>
                <div class="col-lg-4 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-filter"></i>
                        Filter
                    </button>
                    <a href="{{ route('institutions.index') }}" class="btn btn-light-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <h5 class="mb-0">Daftar Unit Kerja</h5>
                <span class="text-muted small">{{ $institutions->count() }} data ditampilkan</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4" style="width: 70px;">No</th>
                            <th>Unit Kerja</th>
                            <th>Alamat</th>
                            <th>Jabatan</th>
                            <th>Status</th>
                            <th class="text-end pe-4" style="width: 130px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($institutions as $institution)
                            @php
                                $status = $statusBadges[$institution->status] ?? ['label' => $institution->status, 'class' => 'bg-light-secondary text-secondary'];
                            @endphp
                            <tr>
                                <td class="ps-4">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $institution->name }}</div>
                                    <div class="data-meta">{{ $institution->level ?? 'Level belum diisi' }}</div>
                                </td>
                                <td class="text-wrap" style="min-width: 220px;">{{ $institution->address ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-light-primary text-primary">{{ $institution->positions_count }} jabatan</span>
                                </td>
                                <td>
                                    <span class="badge {{ $status['class'] }}">{{ $status['label'] }}</span>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="table-actions">
                                        <a href="{{ route('institutions.edit', $institution) }}" class="btn btn-sm btn-light-primary btn-icon" title="Edit">
                                            <i class="ti ti-edit"></i>
                                        </a>

                                        <form action="{{ route('institutions.destroy', $institution) }}" method="POST" onsubmit="return confirm('Hapus unit kerja ini?')">
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
                                <td colspan="6">
                                    <div class="empty-state">
                                        <div class="avtar avtar-l bg-light-secondary text-secondary">
                                            <i class="ti ti-database-off f-28"></i>
                                        </div>
                                        <h5 class="mb-1">Belum ada data unit kerja.</h5>
                                        <p class="text-muted mb-3">Silakan tambahkan unit kerja terlebih dahulu.</p>
                                        <a href="{{ route('institutions.create') }}" class="btn btn-primary">
                                            <i class="ti ti-plus"></i>
                                            Tambah Unit Kerja
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
