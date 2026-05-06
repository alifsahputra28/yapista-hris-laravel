@csrf

<div class="row">
    <div class="col-md-6">
        <div class="form-group mb-3">
            <label for="name" class="form-label">Nama Unit Kerja</label>
            <input
                id="name"
                type="text"
                name="name"
                value="{{ old('name', $institution->name) }}"
                class="form-control @error('name') is-invalid @enderror"
                required
            >

            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group mb-3">
            <label for="level" class="form-label">Level</label>
            <select id="level" name="level" class="form-select @error('level') is-invalid @enderror">
                <option value="">Pilih level</option>
                @foreach (['TK', 'SD', 'SMP', 'SMK', 'Perguruan Tinggi', 'Yayasan'] as $level)
                    <option value="{{ $level }}" @selected(old('level', $institution->level) === $level)>
                        {{ $level }}
                    </option>
                @endforeach
            </select>

            @error('level')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-12">
        <div class="form-group mb-3">
            <label for="address" class="form-label">Alamat</label>
            <textarea
                id="address"
                name="address"
                rows="4"
                class="form-control @error('address') is-invalid @enderror"
            >{{ old('address', $institution->address) }}</textarea>

            @error('address')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group mb-3">
            <label for="status" class="form-label">Status</label>
            <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                <option value="active" @selected(old('status', $institution->status) === 'active')>Active</option>
                <option value="inactive" @selected(old('status', $institution->status) === 'inactive')>Inactive</option>
            </select>

            @error('status')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary">
        <i class="ti ti-device-floppy"></i>
        Simpan
    </button>

    <a href="{{ route('institutions.index') }}" class="btn btn-light-secondary">
        Kembali
    </a>
</div>
