@extends('layouts.admin')

@section('title', 'Dokumen Saya | YAPISTA HRIS')

@section('content')
    @php
        $statusClasses = ['pending' => 'bg-light-warning text-warning', 'valid' => 'bg-light-success text-success', 'rejected' => 'bg-light-danger text-danger'];
        $statusLabels = ['pending' => 'Menunggu', 'valid' => 'Valid', 'rejected' => 'Ditolak'];
    @endphp

    <x-page-header
        title="Dokumen Saya"
        subtitle="Kelola dokumen kepegawaian Anda."
        :breadcrumbs="[
            ['label' => 'Dashboard', 'url' => route('pegawai.dashboard')],
            ['label' => 'Dokumen Saya'],
        ]"
    >
        <x-slot:meta>
            <span>{{ $documents->count() }} dokumen</span><span aria-hidden="true">&bull;</span><span>{{ $documents->where('status', 'valid')->count() }} valid</span>
        </x-slot:meta>
    </x-page-header>

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    @if ($employee->canEditProfileCompletion())
        <section class="content-section" aria-labelledby="upload-heading">
            <div class="content-section-header"><div><h2 id="upload-heading">Unggah Dokumen</h2><p>PDF, JPG, JPEG, atau PNG.</p></div></div>
            <div class="content-section-body">
                <form method="POST" action="{{ route('pegawai.documents.store') }}" enctype="multipart/form-data" class="row g-3">
                    @csrf
                    <div class="col-md-5">
                        <label for="document_type" class="form-label">Jenis Dokumen</label>
                        <select id="document_type" name="document_type" class="form-select @error('document_type') is-invalid @enderror" required>
                            <option value="">Pilih dokumen</option>
                            @foreach ($documentTypes as $value => $label)<option value="{{ $value }}" @selected(old('document_type') === $value)>{{ $label }}</option>@endforeach
                        </select>
                        @error('document_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-5">
                        <label for="file" class="form-label">File</label>
                        <input id="file" type="file" name="file" class="form-control @error('file') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png" required>
                        @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2 d-flex align-items-end"><button type="submit" class="btn btn-primary w-100"><i class="ti ti-upload" aria-hidden="true"></i> Unggah</button></div>
                </form>
            </div>
        </section>
    @elseif (! $employee->isVerified())
        <div class="alert alert-warning">Dokumen sementara tidak dapat diubah selama proses pemeriksaan.</div>
    @endif

    <section class="content-section" aria-labelledby="documents-heading">
        <div class="content-section-header"><h2 id="documents-heading">Daftar Dokumen</h2></div>
        <div class="content-section-body p-0 table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Dokumen</th><th>File</th><th>Status</th><th>Diunggah</th><th>Catatan</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                    @forelse ($documents as $document)
                        <tr>
                            <td><strong>{{ $document->document_type_label }}</strong></td>
                            <td>{{ $document->original_name ?? '-' }}<small class="data-meta d-block">{{ $document->file_size ? number_format($document->file_size / 1024, 1).' KB' : '-' }}</small></td>
                            <td><span class="badge {{ $statusClasses[$document->status] ?? 'bg-light-secondary text-secondary' }}">{{ $statusLabels[$document->status] ?? $document->status }}</span></td>
                            <td>{{ $document->uploaded_at?->format('d M Y H:i') ?? '-' }}</td>
                            <td>{{ $document->note ?? '-' }}</td>
                            <td class="text-end"><div class="table-actions">
                                <a href="{{ route('employee-documents.view', $document) }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-light-primary"><i class="ti ti-eye" aria-hidden="true"></i> Lihat</a>
                                <a href="{{ route('employee-documents.download', $document) }}" class="btn btn-sm btn-light-secondary" aria-label="Unduh {{ $document->document_type_label }}"><i class="ti ti-download" aria-hidden="true"></i></a>
                                @if ($employee->canEditProfileCompletion() && ! $document->isValid())
                                    <form action="{{ route('pegawai.documents.destroy', $document) }}" method="POST" onsubmit="return confirm('Hapus dokumen ini?')">@csrf @method('DELETE')<button type="submit" class="btn btn-sm btn-light-danger" aria-label="Hapus {{ $document->document_type_label }}"><i class="ti ti-trash" aria-hidden="true"></i></button></form>
                                @endif
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="empty-state"><h5 class="mb-1">Belum ada dokumen</h5><p class="text-muted mb-0">Dokumen yang diunggah akan muncul di sini.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
