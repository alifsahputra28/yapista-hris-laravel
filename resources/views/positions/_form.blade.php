@csrf

<div class="row">
    <div class="col-md-6">
        <div class="form-group mb-3">
            <label for="institution_id" class="form-label">Unit Kerja</label>
            <select id="institution_id" name="institution_id" class="form-select @error('institution_id') is-invalid @enderror" required>
                <option value="">Pilih unit kerja</option>
                @foreach ($institutions as $institution)
                    <option value="{{ $institution->id }}" @selected((int) old('institution_id', $position->institution_id) === $institution->id)>
                        {{ $institution->name }}{{ $institution->level ? ' - '.$institution->level : '' }}
                    </option>
                @endforeach
            </select>

            @error('institution_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group mb-3">
            <label for="name" class="form-label">Nama Jabatan</label>
            <input
                id="name"
                type="text"
                name="name"
                value="{{ old('name', $position->name) }}"
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
            <label for="type" class="form-label">Tipe</label>
            <select id="type" name="type" class="form-select @error('type') is-invalid @enderror">
                <option value="">Pilih tipe</option>
                @foreach (['struktural', 'fungsional', 'administratif', 'teknis'] as $type)
                    <option value="{{ $type }}" @selected(old('type', $position->type) === $type)>
                        {{ ucfirst($type) }}
                    </option>
                @endforeach
            </select>

            @error('type')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group mb-3">
            <label for="status" class="form-label">Status</label>
            <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                <option value="active" @selected(old('status', $position->status) === 'active')>Active</option>
                <option value="inactive" @selected(old('status', $position->status) === 'inactive')>Inactive</option>
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

    <a href="{{ route('positions.index') }}" class="btn btn-light-secondary">
        Kembali
    </a>
</div>
