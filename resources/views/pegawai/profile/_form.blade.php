@csrf

@php
    $sameAddress = (bool) old('domicile_same_as_identity', $employee->domicile_same_as_identity);
@endphp

<div class="alert alert-light-primary d-flex flex-wrap gap-2 mb-3" role="note">
    <span><strong>NUP</strong> {{ $employee->formatted_employee_number }}</span>
    <span aria-hidden="true">&bull;</span>
    <span>{{ $employee->institution?->name ?? 'Unit belum ditetapkan' }}</span>
    <span aria-hidden="true">&bull;</span>
    <span>{{ $employee->position?->name ?? 'Jabatan belum ditetapkan' }}</span>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0">Foto Profil</h5></div>
    <div class="card-body">
        <div class="row align-items-center g-3">
            @if ($employee->photo)
                <div class="col-auto"><img src="{{ route('employees.photo', $employee) }}" alt="{{ $employee->full_name }}" class="rounded wid-100 hei-100 object-fit-cover"></div>
            @endif
            <div class="col-md-6">
                <label for="photo" class="form-label">Pilih Foto</label>
                <input id="photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="form-control @error('photo') is-invalid @enderror">
                <div class="form-text">JPG, JPEG, atau PNG. Maksimal 2 MB.</div>
                @error('photo')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0">Identitas Pribadi</h5></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="full_name" class="form-label">Nama Lengkap</label>
                <input id="full_name" type="text" name="full_name" value="{{ old('full_name', $employee->full_name) }}" maxlength="255" class="form-control @error('full_name') is-invalid @enderror" required>
                @error('full_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="nik" class="form-label">NIK</label>
                <input id="nik" type="text" name="nik" value="{{ old('nik', $employee->nik) }}" maxlength="16" inputmode="numeric" autocomplete="off" class="form-control @error('nik') is-invalid @enderror">
                @error('nik')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="family_card_number" class="form-label">Nomor Kartu Keluarga</label>
                <input id="family_card_number" type="text" name="family_card_number" value="{{ old('family_card_number', $employee->family_card_number) }}" maxlength="16" inputmode="numeric" autocomplete="off" class="form-control @error('family_card_number') is-invalid @enderror">
                <div class="form-text">Data ini disimpan secara terenkripsi.</div>
                @error('family_card_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="gender" class="form-label">Jenis Kelamin</label>
                <select id="gender" name="gender" class="form-select @error('gender') is-invalid @enderror">
                    <option value="">Pilih jenis kelamin</option>
                    <option value="male" @selected(old('gender', $employee->gender) === 'male')>Laki-laki</option>
                    <option value="female" @selected(old('gender', $employee->gender) === 'female')>Perempuan</option>
                </select>
                @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="birth_place" class="form-label">Tempat Lahir</label>
                <input id="birth_place" type="text" name="birth_place" value="{{ old('birth_place', $employee->birth_place) }}" maxlength="100" class="form-control @error('birth_place') is-invalid @enderror">
                @error('birth_place')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="birth_date" class="form-label">Tanggal Lahir</label>
                <input id="birth_date" type="date" name="birth_date" value="{{ old('birth_date', $employee->birth_date?->format('Y-m-d')) }}" class="form-control @error('birth_date') is-invalid @enderror">
                @error('birth_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="religion" class="form-label">Agama</label>
                <select id="religion" name="religion" class="form-select @error('religion') is-invalid @enderror">
                    <option value="">Pilih agama</option>
                    @foreach (['islam' => 'Islam', 'kristen' => 'Kristen', 'katolik' => 'Katolik', 'hindu' => 'Hindu', 'buddha' => 'Buddha', 'konghucu' => 'Konghucu'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('religion', $employee->religion) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('religion')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="marital_status" class="form-label">Status Perkawinan</label>
                <select id="marital_status" name="marital_status" class="form-select @error('marital_status') is-invalid @enderror">
                    <option value="">Pilih status</option>
                    @foreach (['single' => 'Belum Menikah', 'married' => 'Menikah', 'divorced' => 'Cerai Hidup', 'widowed' => 'Cerai Mati'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('marital_status', $employee->marital_status) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('marital_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="nationality" class="form-label">Kewarganegaraan</label>
                <input id="nationality" type="text" name="nationality" value="{{ old('nationality', $employee->nationality) }}" maxlength="100" placeholder="Contoh: Indonesia" class="form-control @error('nationality') is-invalid @enderror">
                @error('nationality')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="blood_type" class="form-label">Golongan Darah</label>
                <select id="blood_type" name="blood_type" class="form-select @error('blood_type') is-invalid @enderror">
                    <option value="">Pilih golongan darah</option>
                    @foreach (['A', 'B', 'AB', 'O'] as $bloodType)<option value="{{ $bloodType }}" @selected(old('blood_type', $employee->blood_type) === $bloodType)>{{ $bloodType }}</option>@endforeach
                </select>
                @error('blood_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0">Kontak</h5></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="phone" class="form-label">Nomor HP</label>
                <input id="phone" type="text" name="phone" value="{{ old('phone', $employee->phone) }}" maxlength="30" inputmode="tel" class="form-control @error('phone') is-invalid @enderror">
                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="whatsapp_number" class="form-label">Nomor WhatsApp</label>
                <input id="whatsapp_number" type="text" name="whatsapp_number" value="{{ old('whatsapp_number', $employee->whatsapp_number) }}" maxlength="30" inputmode="tel" class="form-control @error('whatsapp_number') is-invalid @enderror">
                <div class="form-check mt-2"><input id="whatsapp_same_as_phone" type="checkbox" class="form-check-input"><label for="whatsapp_same_as_phone" class="form-check-label">Nomor WhatsApp sama dengan nomor HP</label></div>
                @error('whatsapp_number')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="email" class="form-label">Email Pribadi</label>
                <input id="email" type="email" name="email" value="{{ old('email', $employee->email) }}" maxlength="255" class="form-control @error('email') is-invalid @enderror">
                <div class="form-text">Email ini tidak mengubah email login akun Anda.</div>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0">Alamat KTP dan Domisili</h5></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-12">
                <label for="identity_address" class="form-label">Alamat Sesuai KTP</label>
                <textarea id="identity_address" name="identity_address" rows="3" maxlength="2000" class="form-control @error('identity_address') is-invalid @enderror">{{ old('identity_address', $employee->identity_address) }}</textarea>
                @error('identity_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <input type="hidden" name="domicile_same_as_identity" value="0">
                <div class="form-check">
                    <input id="domicile_same_as_identity" type="checkbox" name="domicile_same_as_identity" value="1" class="form-check-input" @checked($sameAddress)>
                    <label for="domicile_same_as_identity" class="form-check-label">Alamat domisili sama dengan alamat KTP</label>
                </div>
                @error('domicile_same_as_identity')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label for="address" class="form-label">Alamat Domisili</label>
                <textarea id="address" name="address" rows="3" maxlength="2000" class="form-control @error('address') is-invalid @enderror" @readonly($sameAddress)>{{ old('address', $employee->address) }}</textarea>
                @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6"><label for="domicile_province" class="form-label">Provinsi Domisili</label><input id="domicile_province" type="text" name="domicile_province" value="{{ old('domicile_province', $employee->domicile_province) }}" maxlength="100" class="form-control @error('domicile_province') is-invalid @enderror">@error('domicile_province')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label for="domicile_city" class="form-label">Kabupaten/Kota Domisili</label><input id="domicile_city" type="text" name="domicile_city" value="{{ old('domicile_city', $employee->domicile_city) }}" maxlength="100" class="form-control @error('domicile_city') is-invalid @enderror">@error('domicile_city')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label for="domicile_district" class="form-label">Kecamatan Domisili</label><input id="domicile_district" type="text" name="domicile_district" value="{{ old('domicile_district', $employee->domicile_district) }}" maxlength="100" class="form-control @error('domicile_district') is-invalid @enderror">@error('domicile_district')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label for="domicile_village" class="form-label">Kelurahan/Desa Domisili</label><input id="domicile_village" type="text" name="domicile_village" value="{{ old('domicile_village', $employee->domicile_village) }}" maxlength="100" class="form-control @error('domicile_village') is-invalid @enderror">@error('domicile_village')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label for="domicile_postal_code" class="form-label">Kode Pos Domisili</label><input id="domicile_postal_code" type="text" name="domicile_postal_code" value="{{ old('domicile_postal_code', $employee->domicile_postal_code) }}" maxlength="5" inputmode="numeric" class="form-control @error('domicile_postal_code') is-invalid @enderror">@error('domicile_postal_code')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-1">Kontak Darurat</h5>
        <p class="mb-0 text-muted">Kontak ini digunakan oleh HR apabila terjadi keadaan darurat.</p>
    </div>
    <div class="card-body">
        <div class="alert alert-light-secondary py-2">Bagian ini dapat dikosongkan dan dilengkapi kembali nanti.</div>
        <div class="row g-3">
            <div class="col-md-6">
                <label for="emergency_contact_name" class="form-label">Nama Kontak</label>
                <input id="emergency_contact_name" type="text" name="emergency_contact_name" value="{{ old('emergency_contact_name', $employee->emergency_contact_name) }}" maxlength="255" class="form-control @error('emergency_contact_name') is-invalid @enderror">
                @error('emergency_contact_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="emergency_contact_relationship" class="form-label">Hubungan</label>
                <input id="emergency_contact_relationship" type="text" name="emergency_contact_relationship" value="{{ old('emergency_contact_relationship', $employee->emergency_contact_relationship) }}" maxlength="100" placeholder="Contoh: Orang tua, pasangan, atau rekan" class="form-control @error('emergency_contact_relationship') is-invalid @enderror">
                @error('emergency_contact_relationship')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="emergency_contact_phone" class="form-label">Nomor HP</label>
                <input id="emergency_contact_phone" type="text" name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $employee->emergency_contact_phone) }}" maxlength="30" inputmode="tel" class="form-control @error('emergency_contact_phone') is-invalid @enderror">
                @error('emergency_contact_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label for="emergency_contact_address" class="form-label">Alamat</label>
                <textarea id="emergency_contact_address" name="emergency_contact_address" rows="3" maxlength="2000" class="form-control @error('emergency_contact_address') is-invalid @enderror">{{ old('emergency_contact_address', $employee->emergency_contact_address) }}</textarea>
                @error('emergency_contact_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>

<div class="d-flex flex-wrap gap-2 mb-4">
    <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i> Simpan Draft</button>
    <a href="{{ route('pegawai.profile.show') }}" class="btn btn-light-secondary">Batal</a>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const phone = document.getElementById('phone');
    const whatsapp = document.getElementById('whatsapp_number');
    const samePhone = document.getElementById('whatsapp_same_as_phone');
    const identity = document.getElementById('identity_address');
    const domicile = document.getElementById('address');
    const sameAddress = document.getElementById('domicile_same_as_identity');

    samePhone?.addEventListener('change', function () {
        if (this.checked) whatsapp.value = phone.value;
    });

    const syncAddress = function () {
        if (!sameAddress.checked) {
            domicile.readOnly = false;
            return;
        }
        domicile.value = identity.value;
        domicile.readOnly = true;
    };

    sameAddress?.addEventListener('change', syncAddress);
    identity?.addEventListener('input', function () {
        if (sameAddress.checked) domicile.value = identity.value;
    });
    syncAddress();
});
</script>
@endpush
