@php
    $educationLevels = \App\Models\EmployeeEducation::EDUCATION_LEVELS;
    $currentYear = (int) now()->format('Y');
@endphp

<div class="alert alert-light-primary">Data pendidikan dapat dilengkapi secara bertahap. Dokumen ijazah diunggah pada tahap dokumen.</div>

<div class="row g-3">
    <div class="col-md-6">
        <label for="education_level" class="form-label">Jenjang Pendidikan</label>
        <select id="education_level" name="education_level" class="form-select @error('education_level') is-invalid @enderror" required>
            <option value="">Pilih jenjang pendidikan</option>
            @foreach ($educationLevels as $value => $label)
                <option value="{{ $value }}" @selected(old('education_level', $education->education_level) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('education_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label for="institution_name" class="form-label">Nama Institusi</label>
        <input id="institution_name" type="text" name="institution_name" value="{{ old('institution_name', $education->institution_name) }}" maxlength="255" class="form-control @error('institution_name') is-invalid @enderror" required>
        @error('institution_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label for="major" class="form-label">Program Studi/Jurusan</label>
        <input id="major" type="text" name="major" value="{{ old('major', $education->major) }}" maxlength="255" class="form-control @error('major') is-invalid @enderror">
        @error('major')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label for="start_year" class="form-label">Tahun Masuk</label>
        <input id="start_year" type="number" name="start_year" value="{{ old('start_year', $education->start_year) }}" min="1950" max="{{ $currentYear }}" inputmode="numeric" class="form-control @error('start_year') is-invalid @enderror">
        @error('start_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label for="graduation_year" class="form-label">Tahun Lulus</label>
        <input id="graduation_year" type="number" name="graduation_year" value="{{ old('graduation_year', $education->graduation_year) }}" min="1950" max="{{ $currentYear }}" inputmode="numeric" class="form-control @error('graduation_year') is-invalid @enderror">
        @error('graduation_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label for="certificate_number" class="form-label">Nomor Ijazah</label>
        <input id="certificate_number" type="text" name="certificate_number" value="{{ old('certificate_number', $education->certificate_number) }}" maxlength="150" autocomplete="off" class="form-control @error('certificate_number') is-invalid @enderror">
        <div class="form-text">Opsional dan disimpan secara terenkripsi.</div>
        @error('certificate_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label for="degree_prefix" class="form-label">Gelar Depan</label>
        <input id="degree_prefix" type="text" name="degree_prefix" value="{{ old('degree_prefix', $education->degree_prefix) }}" maxlength="50" class="form-control @error('degree_prefix') is-invalid @enderror">
        @error('degree_prefix')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label for="degree_suffix" class="form-label">Gelar Belakang</label>
        <input id="degree_suffix" type="text" name="degree_suffix" value="{{ old('degree_suffix', $education->degree_suffix) }}" maxlength="100" class="form-control @error('degree_suffix') is-invalid @enderror">
        @error('degree_suffix')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <input type="hidden" name="is_highest" value="0">
        <div class="form-check">
            <input id="is_highest" type="checkbox" name="is_highest" value="1" class="form-check-input" @checked((bool) old('is_highest', $education->is_highest))>
            <label for="is_highest" class="form-check-label">Tetapkan sebagai pendidikan tertinggi</label>
        </div>
        @error('is_highest')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </div>
</div>

<div class="d-flex flex-wrap gap-2 mt-4">
    <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i> Simpan</button>
    <a href="{{ route('pegawai.profile.wizard.show', 'education') }}" class="btn btn-light-secondary">Batal</a>
</div>
