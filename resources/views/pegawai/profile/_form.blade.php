@csrf

<div class="row">
    <div class="col-md-6">
        <div class="form-group mb-3">
            <label for="full_name" class="form-label">Nama Lengkap</label>
            <input id="full_name" type="text" name="full_name" value="{{ old('full_name', $employee->full_name) }}" class="form-control @error('full_name') is-invalid @enderror" required>
            @error('full_name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group mb-3">
            <label for="nik" class="form-label">NIK</label>
            <input id="nik" type="text" name="nik" value="{{ old('nik', $employee->nik) }}" class="form-control @error('nik') is-invalid @enderror" required>
            @error('nik')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group mb-3">
            <label for="gender" class="form-label">Jenis Kelamin</label>
            <select id="gender" name="gender" class="form-select @error('gender') is-invalid @enderror">
                <option value="">Pilih jenis kelamin</option>
                <option value="male" @selected(old('gender', $employee->gender) === 'male')>Laki-laki</option>
                <option value="female" @selected(old('gender', $employee->gender) === 'female')>Perempuan</option>
            </select>
            @error('gender')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group mb-3">
            <label for="birth_place" class="form-label">Tempat Lahir</label>
            <input id="birth_place" type="text" name="birth_place" value="{{ old('birth_place', $employee->birth_place) }}" class="form-control @error('birth_place') is-invalid @enderror">
            @error('birth_place')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group mb-3">
            <label for="birth_date" class="form-label">Tanggal Lahir</label>
            <input id="birth_date" type="date" name="birth_date" value="{{ old('birth_date', $employee->birth_date?->format('Y-m-d')) }}" class="form-control @error('birth_date') is-invalid @enderror">
            @error('birth_date')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group mb-3">
            <label for="phone" class="form-label">Nomor HP</label>
            <input id="phone" type="text" name="phone" value="{{ old('phone', $employee->phone) }}" class="form-control @error('phone') is-invalid @enderror" required>
            @error('phone')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-12">
        <div class="form-group mb-3">
            <label for="address" class="form-label">Alamat</label>
            <textarea id="address" name="address" rows="4" class="form-control @error('address') is-invalid @enderror" required>{{ old('address', $employee->address) }}</textarea>
            @error('address')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group mb-3">
            <label for="photo" class="form-label">Foto</label>
            <input id="photo" type="file" name="photo" accept="image/*" class="form-control @error('photo') is-invalid @enderror">
            @error('photo')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror

            @if ($employee->photo)
                <div class="mt-3">
                    <img src="{{ asset('storage/'.$employee->photo) }}" alt="{{ $employee->full_name }}" class="rounded wid-100 hei-100" style="object-fit: cover;">
                </div>
            @endif
        </div>
    </div>
</div>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary">
        <i class="ti ti-device-floppy"></i>
        Simpan
    </button>

    <a href="{{ route('pegawai.profile.show') }}" class="btn btn-light-secondary">Kembali</a>
</div>
