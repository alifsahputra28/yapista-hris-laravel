@csrf

<div class="row g-3">
    <div class="col-md-6">
        <label for="name" class="form-label">Nama <span class="text-danger">*</span></label>
        <input id="name" type="text" name="name" value="{{ old('name', $managedUser->name) }}" class="form-control @error('name') is-invalid @enderror" maxlength="255" autocomplete="name" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
        <input id="email" type="email" name="email" value="{{ old('email', $managedUser->email) }}" class="form-control @error('email') is-invalid @enderror" maxlength="255" autocomplete="email" required>
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
        <select id="role" name="role" class="form-select @error('role') is-invalid @enderror" required>
            @foreach ($roleLabels as $value => $label)
                <option value="{{ $value }}" @selected(old('role', $managedUser->role) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
        <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
            @foreach ($statusLabels as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $managedUser->status) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <label for="employee_id" class="form-label">Pegawai Terkait</label>
        <select id="employee_id" name="employee_id" class="form-select @error('employee_id') is-invalid @enderror">
            <option value="">Tidak terhubung ke data pegawai</option>
            @foreach ($employees as $employee)
                <option value="{{ $employee->id }}" @selected((string) old('employee_id', $managedUser->employee?->id) === (string) $employee->id)>
                    {{ $employee->full_name }} — NUP {{ $employee->employee_number ?: 'belum tersedia' }} — {{ $employee->institution?->name ?: 'unit belum tersedia' }}
                </option>
            @endforeach
        </select>
        <div class="form-text">Wajib untuk role Pegawai. Hanya pegawai yang belum memiliki akun yang ditampilkan.</div>
        @error('employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    @if (! $managedUser->exists)
        <div class="col-md-6">
            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
            <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" minlength="12" autocomplete="new-password" required>
            <div class="form-text">Minimal 12 karakter.</div>
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-6">
            <label for="password_confirmation" class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
            <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" minlength="12" autocomplete="new-password" required>
        </div>
    @endif
</div>

<div class="d-flex flex-column flex-sm-row gap-2 mt-4">
    <button type="submit" class="btn btn-primary">
        <i class="ti ti-device-floppy" aria-hidden="true"></i>
        Simpan
    </button>
    <a href="{{ route('users.index') }}" class="btn btn-light-secondary">Kembali</a>
</div>
