@php
    $statusClass = $item['completed']
        ? 'bg-light-success text-success'
        : ($item['uploaded'] ? 'bg-light-danger text-danger' : 'bg-light-secondary text-secondary');
    $educationId = $educationId ?? null;
    $certificationId = $certificationId ?? null;
@endphp
<tr>
    <td>
        <strong class="d-block">{{ $item['label'] }}</strong>
        @if ($item['original_name'])<small class="text-muted">{{ $item['original_name'] }}</small>@endif
        @if ($item['legacy_fallback'] ?? false)<small class="text-warning d-block">Dokumen legacy umum</small>@endif
    </td>
    <td><span class="badge {{ $item['required'] ? 'bg-light-warning text-warning' : 'bg-light-secondary text-secondary' }}">{{ $item['requirement'] }}</span></td>
    <td><span class="badge {{ $statusClass }}">{{ $item['status'] }}</span></td>
    <td>
        <div class="d-flex flex-wrap justify-content-end gap-2">
            @if ($item['document_id'] && $item['file_available'])
                <a href="{{ route('employee-documents.view', $item['document_id']) }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-light-secondary"><i class="ti ti-eye"></i> Lihat</a>
                <a href="{{ route('employee-documents.download', $item['document_id']) }}" class="btn btn-sm btn-light-secondary"><i class="ti ti-download"></i><span class="visually-hidden">Unduh</span></a>
            @endif
            @if ($editable)
                <form method="POST" action="{{ route('pegawai.documents.store') }}" enctype="multipart/form-data" class="d-flex flex-wrap justify-content-end gap-2" data-wizard-form>
                    @csrf
                    <input type="hidden" name="document_type" value="{{ $documentType }}">
                    <input type="hidden" name="document_context" value="wizard">
                    @if ($educationId)<input type="hidden" name="employee_education_id" value="{{ $educationId }}">@endif
                    @if ($certificationId)<input type="hidden" name="employee_certification_id" value="{{ $certificationId }}">@endif
                    <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" class="form-control form-control-sm" style="max-width: 210px" required aria-label="Pilih {{ $item['label'] }}">
                    <button type="submit" class="btn btn-sm btn-outline-primary"><i class="ti ti-upload"></i> {{ $item['uploaded'] ? 'Ganti' : 'Unggah' }}</button>
                </form>
                @if ($item['document_id'] && $item['document_status'] !== 'valid')
                    <form method="POST" action="{{ route('pegawai.documents.destroy', $item['document_id']) }}" data-confirm-title="Hapus Dokumen?" data-confirm-message="Dokumen ini akan dihapus. Lanjutkan?">
                        @csrf @method('DELETE')
                        <input type="hidden" name="document_context" value="wizard">
                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="ti ti-trash"></i><span class="visually-hidden">Hapus</span></button>
                    </form>
                @endif
            @endif
        </div>
    </td>
</tr>
