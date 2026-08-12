@extends('layouts.admin')

@section('title', 'Dokumen | YAPISTA HRIS')

@section('content')
    @php
        $statusClasses = ['pending' => 'bg-light-warning text-warning', 'valid' => 'bg-light-success text-success', 'rejected' => 'bg-light-danger text-danger'];
        $statusLabels = ['pending' => 'Menunggu', 'valid' => 'Valid', 'rejected' => 'Ditolak'];
        $documentsByType = $documents->keyBy('document_type');
    @endphp

    <x-page-header
        title="Dokumen"
        subtitle="Kelola dokumen kepegawaian Anda."
        :breadcrumbs="[['label' => 'Beranda', 'url' => route('pegawai.dashboard')], ['label' => 'Dokumen']]"
    >
        <x-slot:meta><span>{{ $documents->count() }} dokumen</span><span aria-hidden="true">&bull;</span><span>{{ $documents->where('status', 'valid')->count() }} valid</span></x-slot:meta>
    </x-page-header>

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <section class="d-lg-none mb-4" aria-labelledby="mobile-documents-heading">
        <h2 id="mobile-documents-heading" class="visually-hidden">Daftar jenis dokumen</h2>
        <div class="list-group list-group-flush border-top border-bottom bg-white">
            @foreach ($documentTypes as $type => $label)
                @php
                    $document = $documentsByType->get($type);
                    $mobileStatus = ! $document
                        ? 'Belum tersedia'
                        : ($document->status === 'rejected' ? 'Perlu diperbaiki' : 'Sudah tersedia');
                    $mobileStatusClass = ! $document
                        ? 'text-muted'
                        : ($document->status === 'rejected' ? 'text-danger' : 'text-success');
                    $targetUrl = $document
                        ? route('employee-documents.view', $document)
                        : ($employee->canEditProfileCompletion() ? '#upload-document' : null);
                @endphp

                @if ($targetUrl)
                    <a href="{{ $targetUrl }}" @if ($document) target="_blank" rel="noopener noreferrer" @endif class="list-group-item list-group-item-action px-3 py-3">
                        <div class="d-flex align-items-center justify-content-between gap-3">
                            <div><strong class="d-block">{{ $label }}</strong><span class="small {{ $mobileStatusClass }}">{{ $mobileStatus }}</span></div>
                            <i class="ti ti-chevron-right text-muted" aria-hidden="true"></i>
                        </div>
                    </a>
                @else
                    <div class="list-group-item px-3 py-3">
                        <div class="d-flex align-items-center justify-content-between gap-3">
                            <div><strong class="d-block">{{ $label }}</strong><span class="small {{ $mobileStatusClass }}">{{ $mobileStatus }}</span></div>
                            <i class="ti ti-minus text-muted" aria-hidden="true"></i>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </section>

    @if ($employee->canEditProfileCompletion())
        <section id="upload-document" class="content-section" aria-labelledby="upload-heading">
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

    <section class="card d-none d-lg-block" aria-labelledby="documents-heading">
        <div class="card-header"><h2 id="documents-heading" class="h5 mb-0">Dokumen Tersimpan</h2></div>
        @if ($documents->isEmpty())
            <div class="card-body py-4 text-center"><h3 class="h6 mb-1">Belum ada dokumen</h3><p class="text-muted mb-0">Dokumen yang diunggah akan muncul di sini.</p></div>
        @else
            <div class="list-group list-group-flush">
                @foreach ($documents as $document)
                    <div class="list-group-item px-4 py-3">
                        <div class="d-flex align-items-center justify-content-between gap-3">
                            <div class="d-flex align-items-start gap-3">
                                <span class="avtar avtar-s bg-light-primary text-primary"><i class="ti ti-file-description" aria-hidden="true"></i></span>
                                <div><div class="d-flex flex-wrap align-items-center gap-2 mb-1"><strong>{{ $document->document_type_label }}</strong><span class="badge {{ $statusClasses[$document->status] ?? 'bg-light-secondary text-secondary' }}">{{ $statusLabels[$document->status] ?? $document->status }}</span></div><div class="text-muted small">{{ $document->original_name ?? 'Nama file tidak tersedia' }} &bull; {{ $document->uploaded_at?->format('d M Y, H:i') ?? '-' }}</div>@if (filled($document->note))<div class="small text-danger mt-2">{{ $document->note }}</div>@endif</div>
                            </div>
                            <div class="d-flex gap-2"><a href="{{ route('employee-documents.view', $document) }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-light-primary"><i class="ti ti-eye"></i> Lihat</a><a href="{{ route('employee-documents.download', $document) }}" class="btn btn-sm btn-light-secondary" aria-label="Unduh {{ $document->document_type_label }}"><i class="ti ti-download"></i></a>@if ($employee->canEditProfileCompletion() && ! $document->isValid())<form action="{{ route('pegawai.documents.destroy', $document) }}" method="POST" onsubmit="return confirm('Hapus dokumen ini?')">@csrf @method('DELETE')<button type="submit" class="btn btn-sm btn-light-danger" aria-label="Hapus {{ $document->document_type_label }}"><i class="ti ti-trash"></i></button></form>@endif</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
@endsection
