@props([
    'modalId',
    'title',
    'uploadRoute',
    'templateRoute',
    'requiredColumns' => [],
    'optionalColumns' => [],
    'acceptedFormats' => 'XLSX, XLS, CSV',
])

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ $uploadRoute }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <div class="d-flex align-items-center gap-2">
                        <span class="avtar avtar-s bg-light-success text-success" aria-hidden="true">
                            <i class="ti ti-table"></i>
                        </span>
                        <h2 class="modal-title h5 mb-0" id="{{ $modalId }}-title">{{ $title }}</h2>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label for="{{ $modalId }}-file" class="form-label">File Excel <span class="text-danger">*</span></label>
                        <input
                            id="{{ $modalId }}-file"
                            type="file"
                            name="file"
                            class="form-control @error('file') is-invalid @enderror"
                            accept=".xlsx,.xls,.csv"
                            required
                        >
                        @error('file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Format yang didukung: {{ $acceptedFormats }}. Ukuran maksimal 5 MB.</div>
                    </div>

                    <div class="border rounded p-3 mb-3">
                        <div class="fw-semibold mb-2">Struktur data</div>
                        <p class="text-muted small mb-2">Download template agar urutan dan nama kolom sesuai dengan importer.</p>
                        @if ($requiredColumns)
                            <div class="small mb-1"><span class="fw-medium">Wajib diisi:</span> {{ implode(', ', $requiredColumns) }}.</div>
                        @endif
                        @if ($optionalColumns)
                            <div class="small text-muted"><span class="fw-medium">Opsional:</span> {{ implode(', ', $optionalColumns) }}.</div>
                        @endif
                    </div>

                    <a href="{{ $templateRoute }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
                        <i class="ti ti-download" aria-hidden="true"></i>
                        <span>Download Template Excel</span>
                    </a>
                </div>

                <div class="modal-footer d-flex flex-column-reverse flex-sm-row">
                    <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary d-inline-flex align-items-center justify-content-center gap-2">
                        <i class="ti ti-file-upload" aria-hidden="true"></i>
                        <span>Import Data</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if ($errors->has('file'))
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modalElement = document.getElementById(@js($modalId));

                if (modalElement && window.bootstrap) {
                    window.bootstrap.Modal.getOrCreateInstance(modalElement).show();
                }
            });
        </script>
    @endpush
@endif
