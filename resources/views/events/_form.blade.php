@php
    $targetTypes = \App\Models\Event::TARGET_TYPES;
    $targetType = old('target_type', $event->target_type ?? 'all');
    $selectedInstitutionIds = collect(old('institution_ids', []))->map(fn ($id) => (string) $id)->all();
    $selectedPositionIds = collect(old('position_ids', []))->map(fn ($id) => (string) $id)->all();
    $selectedEmployeeIds = collect(old('employee_ids', []))->map(fn ($id) => (string) $id)->all();
@endphp

@csrf

<div class="row">
    <div class="col-md-8">
        <div class="form-group mb-3">
            <label for="name" class="form-label">Nama Kegiatan</label>
            <input
                id="name"
                type="text"
                name="name"
                value="{{ old('name', $event->name) }}"
                class="form-control @error('name') is-invalid @enderror"
                required
            >
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group mb-3">
            <label for="event_date" class="form-label">Tanggal Kegiatan</label>
            <input
                id="event_date"
                type="date"
                name="event_date"
                value="{{ old('event_date', $event->event_date?->format('Y-m-d')) }}"
                class="form-control @error('event_date') is-invalid @enderror"
                required
            >
            @error('event_date')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group mb-3">
            <label for="start_time" class="form-label">Jam Mulai</label>
            <input
                id="start_time"
                type="time"
                name="start_time"
                value="{{ old('start_time', $event->start_time?->format('H:i')) }}"
                class="form-control @error('start_time') is-invalid @enderror"
            >
            @error('start_time')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group mb-3">
            <label for="end_time" class="form-label">Jam Selesai</label>
            <input
                id="end_time"
                type="time"
                name="end_time"
                value="{{ old('end_time', $event->end_time?->format('H:i')) }}"
                class="form-control @error('end_time') is-invalid @enderror"
            >
            @error('end_time')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group mb-3">
            <label for="location" class="form-label">Lokasi</label>
            <input
                id="location"
                type="text"
                name="location"
                value="{{ old('location', $event->location) }}"
                class="form-control @error('location') is-invalid @enderror"
            >
            @error('location')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-12">
        <div class="form-group mb-3">
            <label for="description" class="form-label">Deskripsi</label>
            <textarea
                id="description"
                name="description"
                rows="4"
                class="form-control @error('description') is-invalid @enderror"
            >{{ old('description', $event->description) }}</textarea>
            @error('description')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group mb-3">
            <label for="target_type" class="form-label">Target Peserta</label>
            <select id="target_type" name="target_type" class="form-select js-target-type @error('target_type') is-invalid @enderror" required>
                @foreach ($targetTypes as $value => $label)
                    <option value="{{ $value }}" @selected($targetType === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('target_type')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    @if ($event->exists)
        <div class="col-md-6">
            <div class="form-check mt-4 pt-2">
                <input id="regenerate_participants" type="checkbox" name="regenerate_participants" value="1" class="form-check-input" @checked(old('regenerate_participants'))>
                <label for="regenerate_participants" class="form-check-label">Generate ulang peserta berdasarkan target ini</label>
            </div>
        </div>
    @endif

    <div class="col-12" data-target-section="institution">
        <div class="form-group mb-3">
            <label for="institution_ids" class="form-label">Unit Kerja Target</label>
            <select id="institution_ids" name="institution_ids[]" class="form-select @error('institution_ids') is-invalid @enderror" multiple size="6">
                @foreach ($institutions as $institution)
                    <option value="{{ $institution->id }}" @selected(in_array((string) $institution->id, $selectedInstitutionIds, true))>
                        {{ $institution->name }}{{ $institution->level ? ' - '.$institution->level : '' }}
                    </option>
                @endforeach
            </select>
            @error('institution_ids')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-12" data-target-section="position">
        <div class="form-group mb-3">
            <label for="position_ids" class="form-label">Jabatan Target</label>
            <select id="position_ids" name="position_ids[]" class="form-select @error('position_ids') is-invalid @enderror" multiple size="6">
                @foreach ($positions as $position)
                    <option value="{{ $position->id }}" @selected(in_array((string) $position->id, $selectedPositionIds, true))>
                        {{ $position->name }}{{ $position->institution ? ' - '.$position->institution->name : '' }}
                    </option>
                @endforeach
            </select>
            @error('position_ids')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-12" data-target-section="selected">
        <div class="form-group mb-3">
            <label for="employee_ids" class="form-label">Pegawai Target</label>
            <select id="employee_ids" name="employee_ids[]" class="form-select @error('employee_ids') is-invalid @enderror" multiple size="8">
                @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}" @selected(in_array((string) $employee->id, $selectedEmployeeIds, true))>
                        {{ $employee->full_name }} - {{ $employee->employee_number }}{{ $employee->institution ? ' - '.$employee->institution->name : '' }}
                    </option>
                @endforeach
            </select>
            @error('employee_ids')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary">
        <i class="ti ti-device-floppy"></i>
        Simpan
    </button>

    <a href="{{ $event->exists ? route('events.show', $event) : route('events.index') }}" class="btn btn-light-secondary">
        Kembali
    </a>
</div>

@push('scripts')
    <script>
        document.querySelectorAll('.js-target-form').forEach((form) => {
            const select = form.querySelector('.js-target-type');
            const sections = form.querySelectorAll('[data-target-section]');

            if (!select) {
                return;
            }

            const syncTargetSections = () => {
                sections.forEach((section) => {
                    section.classList.toggle('d-none', section.dataset.targetSection !== select.value);
                });
            };

            select.addEventListener('change', syncTargetSections);
            syncTargetSections();
        });
    </script>
@endpush
