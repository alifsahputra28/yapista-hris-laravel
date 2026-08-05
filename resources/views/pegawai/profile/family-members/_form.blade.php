@php
    $relationships = \App\Models\EmployeeFamilyMember::RELATIONSHIPS;
    $bpjsStatuses = \App\Models\EmployeeFamilyMember::BPJS_STATUSES;
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label for="full_name" class="form-label">Nama Lengkap</label>
        <input id="full_name" type="text" name="full_name" value="{{ old('full_name', $familyMember->full_name) }}" maxlength="255" class="form-control @error('full_name') is-invalid @enderror" required>
        @error('full_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label for="relationship" class="form-label">Hubungan</label>
        <select id="relationship" name="relationship" class="form-select @error('relationship') is-invalid @enderror" required>
            <option value="">Pilih hubungan</option>
            @foreach ($relationships as $value => $label)
                <option value="{{ $value }}" @selected(old('relationship', $familyMember->relationship) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('relationship')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label for="nik" class="form-label">NIK</label>
        <input id="nik" type="text" name="nik" value="{{ old('nik', $familyMember->nik) }}" maxlength="16" inputmode="numeric" autocomplete="off" class="form-control @error('nik') is-invalid @enderror">
        <div class="form-text">Opsional dan disimpan secara terenkripsi.</div>
        @error('nik')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label for="gender" class="form-label">Jenis Kelamin</label>
        <select id="gender" name="gender" class="form-select @error('gender') is-invalid @enderror">
            <option value="">Pilih jenis kelamin</option>
            <option value="male" @selected(old('gender', $familyMember->gender) === 'male')>Laki-laki</option>
            <option value="female" @selected(old('gender', $familyMember->gender) === 'female')>Perempuan</option>
        </select>
        @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label for="birth_place" class="form-label">Tempat Lahir</label>
        <input id="birth_place" type="text" name="birth_place" value="{{ old('birth_place', $familyMember->birth_place) }}" maxlength="100" class="form-control @error('birth_place') is-invalid @enderror">
        @error('birth_place')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label for="birth_date" class="form-label">Tanggal Lahir</label>
        <input id="birth_date" type="date" name="birth_date" value="{{ old('birth_date', $familyMember->birth_date?->format('Y-m-d')) }}" class="form-control @error('birth_date') is-invalid @enderror">
        @error('birth_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label for="occupation" class="form-label">Pekerjaan</label>
        <input id="occupation" type="text" name="occupation" value="{{ old('occupation', $familyMember->occupation) }}" maxlength="150" class="form-control @error('occupation') is-invalid @enderror">
        @error('occupation')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label for="bpjs_status" class="form-label">Status BPJS</label>
        <select id="bpjs_status" name="bpjs_status" class="form-select @error('bpjs_status') is-invalid @enderror">
            <option value="">Pilih status BPJS</option>
            @foreach ($bpjsStatuses as $value => $label)
                <option value="{{ $value }}" @selected(old('bpjs_status', $familyMember->bpjs_status) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('bpjs_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <input type="hidden" name="is_dependent" value="0">
        <div class="form-check">
            <input id="is_dependent" type="checkbox" name="is_dependent" value="1" class="form-check-input" @checked((bool) old('is_dependent', $familyMember->is_dependent))>
            <label for="is_dependent" class="form-check-label">Termasuk tanggungan pegawai</label>
        </div>
        @error('is_dependent')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </div>
</div>

<div class="d-flex flex-wrap gap-2 mt-4">
    <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i> Simpan</button>
    <a href="{{ route('pegawai.profile.show') }}" class="btn btn-light-secondary">Batal</a>
</div>
