@extends('layouts.admin')

@section('title', 'Jabatan | YAPISTA HRIS')

@section('content')
    <div class="page-header">
        <div class="page-block">
            <div class="row align-items-center">
                <div class="col-md-12">
                    <div class="page-header-title">
                        <h5 class="m-b-10">Jabatan</h5>
                    </div>

                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item" aria-current="page">Master Data</li>
                        <li class="breadcrumb-item" aria-current="page">Jabatan</li>
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
                <h5 class="mb-0">Master Jabatan</h5>

                <a href="{{ route('positions.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus"></i>
                    Tambah Jabatan
                </a>
            </div>
        </div>

        <div class="card-body">
            <form method="GET" action="{{ route('positions.index') }}" class="mb-3">
                <div class="input-group">
                    <input
                        type="search"
                        name="search"
                        value="{{ $search }}"
                        class="form-control"
                        placeholder="Cari jabatan atau unit kerja"
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
                            <th>Nama Jabatan</th>
                            <th>Unit Kerja</th>
                            <th>Tipe</th>
                            <th>Status</th>
                            <th class="text-end" style="width: 160px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($positions as $position)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $position->name }}</td>
                                <td>{{ $position->institution?->name ?? '-' }}</td>
                                <td>{{ $position->type ?? '-' }}</td>
                                <td>
                                    <span class="badge {{ $position->status === 'active' ? 'bg-light-success text-success' : 'bg-light-danger text-danger' }}">
                                        {{ $position->status }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('positions.edit', $position) }}" class="btn btn-sm btn-light-primary">
                                        <i class="ti ti-edit"></i>
                                        Edit
                                    </a>

                                    <form action="{{ route('positions.destroy', $position) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus jabatan ini?')">
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
                                <td colspan="6" class="text-center text-muted">Belum ada data jabatan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
