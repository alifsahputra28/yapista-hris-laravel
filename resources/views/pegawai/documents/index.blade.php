@extends('layouts.admin')

@section('title', 'Dokumen Saya | YAPISTA HRIS')

@section('content')
    @php
        $statusClasses = [
            'pending' => 'bg-light-warning text-warning',
            'valid' => 'bg-light-success text-success',
            'rejected' => 'bg-light-danger text-danger',
        ];
        $statusLabels = [
            'pending' => 'Menunggu',
            'valid' => 'Valid',
            'rejected' => 'Ditolak',
        ];
    @endphp

    <div class="page-header">
        <div class="page-block">
            <div class="row align-items-center">
                <div class="col-md-12">
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

    <div class="card page-intro-card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <h4 class="mb-1">Dokumen Saya</h4>
                    <p class="mb-0 text-muted">Upload atau ganti dokumen identitas dan dokumen pendukung untuk verifikasi HR.</p>
                </div>

                <a href="{{ route('pegawai.profile.show') }}" class="btn btn-light-secondary">
                    <i class="ti ti-user"></i>
                    Profil Saya
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card summary-card">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avtar avtar-s bg-light-primary text-primary">
                            <i class="ti ti-files f-20"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Total Dokumen</div>
                            <h4 class="mb-0">{{ $documents->count() }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card summary-card">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avtar avtar-s bg-light-success text-success">
                            <i class="ti ti-circle-check f-20"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Dokumen Valid</div>
                            <h4 class="mb-0">{{ $documents->where('status', 'valid')->count() }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card summary-card">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avtar avtar-s bg-light-warning text-warning">
                            <i class="ti ti-clock f-20"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Menunggu</div>
                            <h4 class="mb-0">{{ $documents->where('status', 'pending')->count() }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
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
                                        {{ $statusLabels[$document->status] ?? $document->status }}
                                    </span>
                                </td>
                                <td>{{ $document->uploaded_at?->format('d M Y H:i') ?? '-' }}</td>
                                <td>{{ $document->note ?? '-' }}</td>
                                <td class="text-end">
                                    <div class="table-actions">
                                        <a href="{{ asset('storage/'.$document->file_path) }}" target="_blank" class="btn btn-sm btn-light-primary">
                                            <i class="ti ti-download"></i>
                                            Lihat
                                        </a>

                                        @if ($employee->canEditProfile() && ! $document->isValid())
                                            <form action="{{ route('pegawai.documents.destroy', $document) }}" method="POST" onsubmit="return confirm('Hapus dokumen ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-light-danger">
                                                    <i class="ti ti-trash"></i>
                                                    Hapus
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state">
                                        <div class="avtar avtar-l bg-light-secondary text-secondary">
                                            <i class="ti ti-files-off f-28"></i>
                                        </div>
                                        <h5 class="mb-1">Belum ada dokumen.</h5>
                                        <p class="text-muted mb-0">Upload dokumen KTP terlebih dahulu agar profil bisa diajukan.</p>
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
