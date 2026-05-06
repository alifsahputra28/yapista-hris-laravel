@php
    $employeeTypes = [
        'guru' => 'Guru',
        'dosen' => 'Dosen',
        'tenaga_kependidikan' => 'Tenaga Kependidikan',
        'staff_yayasan' => 'Staff Yayasan',
        'security' => 'Security',
        'cleaning_service' => 'Cleaning Service',
        'driver' => 'Driver',
        'teknisi' => 'Teknisi',
    ];
    $employmentStatuses = [
        'aktif' => 'Aktif',
        'kontrak' => 'Kontrak',
        'honorer' => 'Honorer',
        'part_time' => 'Part Time',
        'nonaktif' => 'Nonaktif',
        'resign' => 'Resign',
    ];
@endphp

@csrf

<div class="row">
    <div class="col-md-6">
        <div class="form-group mb-3">
            <label for="full_name" class="form-label">Nama Lengkap</label>
            <input
                id="full_name"
                type="text"
                name="full_name"
                value="{{ old('full_name', $employee->full_name) }}"
                class="form-control @error('full_name') is-invalid @enderror"
                required
            >

            @error('full_name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group mb-3">
            <label for="email" class="form-label">Email</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email', $employee->email) }}"
                class="form-control @error('email') is-invalid @enderror"
            >

            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group mb-3">
            <label for="nik" class="form-label">NIK</label>
            <input
                id="nik"
                type="text"
                name="nik"
                value="{{ old('nik', $employee->nik) }}"
                class="form-control @error('nik') is-invalid @enderror"
            >

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
            <input
                id="birth_place"
                type="text"
                name="birth_place"
                value="{{ old('birth_place', $employee->birth_place) }}"
                class="form-control @error('birth_place') is-invalid @enderror"
            >

            @error('birth_place')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group mb-3">
            <label for="birth_date" class="form-label">Tanggal Lahir</label>
            <input
                id="birth_date"
                type="date"
                name="birth_date"
                value="{{ old('birth_date', $employee->birth_date?->format('Y-m-d')) }}"
                class="form-control @error('birth_date') is-invalid @enderror"
            >

            @error('birth_date')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group mb-3">
            <label for="phone" class="form-label">Nomor HP</label>
            <input
                id="phone"
                type="text"
                name="phone"
                value="{{ old('phone', $employee->phone) }}"
                class="form-control @error('phone') is-invalid @enderror"
            >

            @error('phone')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group mb-3">
            <label for="join_date" class="form-label">Tanggal Mulai Kerja</label>
            <input
                id="join_date"
                type="date"
                name="join_date"
                value="{{ old('join_date', $employee->join_date?->format('Y-m-d')) }}"
                class="form-control @error('join_date') is-invalid @enderror"
            >

            @error('join_date')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group mb-3">
            <label for="foundation_registry_number" class="form-label">Nomor Urut Buku Yayasan</label>
            <input
                id="foundation_registry_number"
                type="number"
                name="foundation_registry_number"
                value="{{ old('foundation_registry_number', $employee->foundation_registry_number) }}"
                min="1"
                placeholder="25"
                class="form-control @error('foundation_registry_number') is-invalid @enderror"
            >

            @error('foundation_registry_number')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group mb-3">
            <label for="institution_id" class="form-label">Unit Kerja</label>
            <select id="institution_id" name="institution_id" class="form-select @error('institution_id') is-invalid @enderror" required>
                <option value="">Pilih unit kerja</option>
                @foreach ($institutions as $institution)
                    <option value="{{ $institution->id }}" @selected((int) old('institution_id', $employee->institution_id) === $institution->id)>
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
            <label for="position_id" class="form-label">Jabatan</label>
            <select id="position_id" name="position_id" class="form-select @error('position_id') is-invalid @enderror" required>
                <option value="">Pilih jabatan</option>
                @foreach ($positions as $position)
                    <option value="{{ $position->id }}" @selected((int) old('position_id', $employee->position_id) === $position->id)>
                        {{ $position->name }}{{ $position->institution ? ' - '.$position->institution->name : '' }}
                    </option>
                @endforeach
            </select>

            @error('position_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group mb-3">
            <label for="employee_type" class="form-label">Jenis Pegawai</label>
            <select id="employee_type" name="employee_type" class="form-select @error('employee_type') is-invalid @enderror" required>
                <option value="">Pilih jenis pegawai</option>
                @foreach ($employeeTypes as $value => $label)
                    <option value="{{ $value }}" @selected(old('employee_type', $employee->employee_type) === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>

            @error('employee_type')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group mb-3">
            <label for="employment_status" class="form-label">Status Kepegawaian</label>
            <select id="employment_status" name="employment_status" class="form-select @error('employment_status') is-invalid @enderror" required>
                @foreach ($employmentStatuses as $value => $label)
                    <option value="{{ $value }}" @selected(old('employment_status', $employee->employment_status) === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>

            @error('employment_status')
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
            >{{ old('address', $employee->address) }}</textarea>

            @error('address')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group mb-3">
            <label for="photo" class="form-label">Foto</label>
            <input
                id="photo"
                type="file"
                name="photo"
                accept="image/*"
                class="form-control @error('photo') is-invalid @enderror"
            >

            @error('photo')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror

            @if ($employee->photo)
                <small class="text-muted d-block mt-2">Foto saat ini tersimpan.</small>
            @endif
        </div>
    </div>
</div>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary">
        <i class="ti ti-device-floppy"></i>
        Simpan
    </button>

    <a href="{{ route('employees.index') }}" class="btn btn-light-secondary">
        Kembali
    </a>
</div>
