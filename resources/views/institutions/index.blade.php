@extends('layouts.admin')

@section('title', 'Unit Kerja | YAPISTA HRIS')

@section('content')
    <div class="page-header">
        <div class="page-block">
            <div class="row align-items-center">
                <div class="col-md-12">
                    <div class="page-header-title">
                        <h5 class="m-b-10">Unit Kerja</h5>
                    </div>

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

    <div class="card">
        <div class="card-header">
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <h5 class="mb-0">Master Unit Kerja</h5>

                <a href="{{ route('institutions.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus"></i>
                    Tambah Unit Kerja
                </a>
            </div>
        </div>

        <div class="card-body">
            <form method="GET" action="{{ route('institutions.index') }}" class="mb-3">
                <div class="input-group">
                    <input
                        type="search"
                        name="search"
                        value="{{ $search }}"
                        class="form-control"
                        placeholder="Cari unit kerja"
                    >
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="ti ti-search"></i>
                    </button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover table-borderless mb-0">
                    <thead>
                        <tr>
                            <th style="width: 70px;">No</th>
                            <th>Nama Unit</th>
                            <th>Level</th>
                            <th>Alamat</th>
                            <th>Jabatan</th>
                            <th>Status</th>
                            <th class="text-end" style="width: 160px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($institutions as $institution)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $institution->name }}</td>
                                <td>{{ $institution->level ?? '-' }}</td>
                                <td>{{ $institution->address ?? '-' }}</td>
                                <td>{{ $institution->positions_count }}</td>
                                <td>
                                    <span class="badge {{ $institution->status === 'active' ? 'bg-light-success text-success' : 'bg-light-danger text-danger' }}">
                                        {{ $institution->status }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('institutions.edit', $institution) }}" class="btn btn-sm btn-light-primary">
                                        <i class="ti ti-edit"></i>
                                        Edit
                                    </a>

                                    <form action="{{ route('institutions.destroy', $institution) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus unit kerja ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light-danger">
                                            <i class="ti ti-trash"></i>
                                            Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">Belum ada data unit kerja.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
