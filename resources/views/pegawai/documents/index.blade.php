@extends('layouts.admin')

@section('title', 'Dokumen Saya | YAPISTA HRIS')

@section('content')
    @php
        $statusClasses = [
            'pending' => 'bg-light-warning text-warning',
            'valid' => 'bg-light-success text-success',
            'rejected' => 'bg-light-danger text-danger',
        ];
    @endphp

    <div class="page-header">
        <div class="page-block">
            <div class="row align-items-center">
                <div class="col-md-12">
                    <div class="page-header-title">
                        <h5 class="m-b-10">Dokumen Saya</h5>
                    </div>

                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('pegawai.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item" aria-current="page">Dokumen Saya</li>
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

    @if ($employee->canEditProfile())
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Upload Dokumen</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('pegawai.documents.store') }}" enctype="multipart/form-data" class="row g-3">
                    @csrf
                    <div class="col-md-5">
                        <label for="document_type" class="form-label">Jenis Dokumen</label>
                        <select id="document_type" name="document_type" class="form-select @error('document_type') is-invalid @enderror" required>
                            <option value="">Pilih dokumen</option>
                            @foreach ($documentTypes as $value => $label)
                                <option value="{{ $value }}" @selected(old('document_type') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('document_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-5">
                        <label for="file" class="form-label">File</label>
                        <input id="file" type="file" name="file" class="form-control @error('file') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png" required>
                        @error('file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ti ti-upload"></i>
                            Upload
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @else
        <div class="alert alert-warning">Dokumen tidak bisa diubah sementara karena data sudah diajukan atau diverifikasi.</div>
    @endif

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Daftar Dokumen</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-borderless mb-0">
                    <thead>
                        <tr>
                            <th style="width: 70px;">No</th>
                            <th>Jenis Dokumen</th>
                            <th>Nama File</th>
                            <th>Ukuran</th>
                            <th>Status</th>
                            <th>Tanggal Upload</th>
                            <th>Catatan</th>
                            <th class="text-end" style="width: 180px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($documents as $document)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $document->document_type_label }}</td>
                                <td>{{ $document->original_name ?? '-' }}</td>
                                <td>{{ $document->file_size ? number_format($document->file_size / 1024, 1).' KB' : '-' }}</td>
                                <td>
                                    <span class="badge {{ $statusClasses[$document->status] ?? 'bg-light-secondary text-secondary' }}">
                                        {{ $document->status }}
                                    </span>
                                </td>
                                <td>{{ $document->uploaded_at?->format('d M Y H:i') ?? '-' }}</td>
                                <td>{{ $document->note ?? '-' }}</td>
                                <td class="text-end">
                                    <a href="{{ asset('storage/'.$document->file_path) }}" target="_blank" class="btn btn-sm btn-light-primary">
                                        <i class="ti ti-download"></i>
                                        Lihat
                                    </a>

                                    @if ($employee->canEditProfile() && ! $document->isValid())
                                        <form action="{{ route('pegawai.documents.destroy', $document) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus dokumen ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light-danger">
                                                <i class="ti ti-trash"></i>
                                                Hapus
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">Belum ada dokumen.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
