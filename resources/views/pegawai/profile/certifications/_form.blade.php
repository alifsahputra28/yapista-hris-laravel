@php
    $activeValue = (bool) old('is_active', $certification->exists ? $certification->is_active : true);
@endphp

<div class="alert alert-light-primary">File sertifikat akan diunggah pada bagian dokumen.</div>

<div class="row g-3">
    <div class="col-md-6">
        <label for="name" class="form-label">Nama Sertifikasi</label>
        <input id="name" type="text" name="name" value="{{ old('name', $certification->name) }}" maxlength="255" class="form-control @error('name') is-invalid @enderror" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label for="certificate_number" class="form-label">Nomor Sertifikat</label>
        <input id="certificate_number" type="text" name="certificate_number" value="{{ old('certificate_number', $certification->certificate_number) }}" maxlength="150" autocomplete="off" class="form-control @error('certificate_number') is-invalid @enderror">
        <div class="form-text">Opsional dan disimpan secara terenkripsi.</div>
        @error('certificate_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label for="issuer" class="form-label">Lembaga Penerbit</label>
        <input id="issuer" type="text" name="issuer" value="{{ old('issuer', $certification->issuer) }}" maxlength="255" class="form-control @error('issuer') is-invalid @enderror">
        @error('issuer')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label for="competency_field" class="form-label">Bidang Kompetensi</label>
        <input id="competency_field" type="text" name="competency_field" value="{{ old('competency_field', $certification->competency_field) }}" maxlength="255" class="form-control @error('competency_field') is-invalid @enderror">
        @error('competency_field')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label for="issued_at" class="form-label">Tanggal Terbit</label>
        <input id="issued_at" type="date" name="issued_at" value="{{ old('issued_at', $certification->issued_at?->format('Y-m-d')) }}" class="form-control @error('issued_at') is-invalid @enderror">
        @error('issued_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label for="expired_at" class="form-label">Tanggal Kedaluwarsa</label>
        <input id="expired_at" type="date" name="expired_at" value="{{ old('expired_at', $certification->expired_at?->format('Y-m-d')) }}" class="form-control @error('expired_at') is-invalid @enderror">
        @error('expired_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <input type="hidden" name="is_active" value="0">
        <div class="form-check">
            <input id="is_active" type="checkbox" name="is_active" value="1" class="form-check-input" @checked($activeValue)>
            <label for="is_active" class="form-check-label">Sertifikasi aktif secara administratif</label>
        </div>
        @error('is_active')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </div>
</div>

<div class="d-flex flex-wrap gap-2 mt-4">
    <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i> Simpan</button>
    <a href="{{ route('pegawai.profile.wizard.show', 'education') }}" class="btn btn-light-secondary">Batal</a>
</div>
