@extends('layouts.admin')

@section('title', 'Peserta Kegiatan | YAPISTA HRIS')

@section('content')
    @php
        $participantStatuses = \App\Models\EventParticipant::STATUSES;
        $participantStatusClasses = [
            'invited' => 'bg-light-warning text-warning',
            'confirmed' => 'bg-light-success text-success',
            'cancelled' => 'bg-light-danger text-danger',
        ];
        $activeInstitution = $institutions->firstWhere('id', (int) request('institution_id'))?->name;
        $activePosition = $positions->firstWhere('id', (int) request('position_id'))?->name;
        $advancedFilterCount = request()->filled('position_id') ? 1 : 0;
        $hasActiveFilters = collect(['search', 'participant_status', 'institution_id', 'position_id'])->contains(fn ($key) => request()->filled($key));
        $activeFilterCount = collect(['participant_status', 'institution_id', 'position_id'])->filter(fn ($key) => request()->filled($key))->count();
    @endphp

    <x-page-header
        title="Peserta Kegiatan"
        subtitle="Kelola peserta untuk kegiatan {{ $event->name }}."
        :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Kegiatan', 'url' => route('events.index')], ['label' => 'Detail', 'url' => route('events.show', $event)], ['label' => 'Peserta']]"
    >
        <x-slot:actions><a href="{{ route('events.show', $event) }}" class="btn btn-light-secondary">Kembali</a></x-slot:actions>
    </x-page-header>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    @if ($event->isDraft())
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Tambah Peserta Manual</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('events.participants.manual', $event) }}">
                    @csrf
                    <div class="row g-2">
                        <div class="col-md-9">
                            <select name="employee_ids[]" class="form-select" multiple size="6" required>
                                @foreach ($eligibleEmployees as $employee)
                                    <option value="{{ $employee->id }}">
                                        {{ $employee->full_name }} - {{ $employee->employee_number }}{{ $employee->institution ? ' - '.$employee->institution->name : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3 d-grid align-self-start">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-user-plus"></i>
                                Tambah Peserta
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="card filter-card">
        <div class="card-header"><h5 class="mb-0">Filter Peserta</h5></div>
        <div class="card-body">
            <form id="participant-filter-form" method="GET" action="{{ route('events.participants.index', $event) }}" class="row g-3 align-items-end">
                <div class="col-lg-5"><label for="search" class="form-label">Cari Peserta</label><div class="filter-search-wrap"><i class="ti ti-search" aria-hidden="true"></i><input id="search" type="search" name="search" value="{{ $search }}" class="form-control" placeholder="Cari nama atau NUP..." aria-label="Cari peserta kegiatan"></div></div>
                <div class="col-12 d-lg-none"><button class="btn btn-light-primary w-100" type="button" data-bs-toggle="collapse" data-bs-target=".participant-mobile-filter" aria-expanded="{{ $activeFilterCount ? 'true' : 'false' }}"><i class="ti ti-adjustments-horizontal" aria-hidden="true"></i> Filter @if ($activeFilterCount)({{ $activeFilterCount }})@endif</button></div>
                <div class="col-md-6 col-lg-3 collapse d-lg-block participant-mobile-filter {{ $activeFilterCount ? 'show' : '' }}"><label for="participant_status" class="form-label">Status Peserta</label><select id="participant_status" name="participant_status" class="form-select"><option value="">Semua status</option>@foreach ($participantStatuses as $value => $label)<option value="{{ $value }}" @selected(request('participant_status') === $value)>{{ $label }}</option>@endforeach</select></div>
                <div class="col-md-6 col-lg-3 collapse d-lg-block participant-mobile-filter {{ $activeFilterCount ? 'show' : '' }}"><label for="institution_id" class="form-label">Unit Kerja</label><select id="institution_id" name="institution_id" class="form-select"><option value="">Semua unit</option>@foreach ($institutions as $institution)<option value="{{ $institution->id }}" @selected((string) request('institution_id') === (string) $institution->id)>{{ $institution->name }}</option>@endforeach</select></div>
                <div class="col-lg-1 filter-primary-actions collapse d-lg-block participant-mobile-filter {{ $activeFilterCount ? 'show' : '' }}"><button type="submit" class="btn btn-primary w-100" title="Terapkan Filter"><i class="ti ti-filter" aria-hidden="true"></i><span class="d-lg-none">Terapkan Filter</span></button></div>
            </form>
            <div class="filter-secondary-row collapse d-lg-flex participant-mobile-filter {{ $activeFilterCount ? 'show' : '' }}">
                <button class="btn btn-link filter-advanced-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#participant-advanced-filter" aria-expanded="{{ $advancedFilterCount ? 'true' : 'false' }}" aria-controls="participant-advanced-filter"><i class="ti ti-adjustments-horizontal" aria-hidden="true"></i>Filter Lanjutan @if ($advancedFilterCount)<span class="badge bg-light-primary text-primary">{{ $advancedFilterCount }}</span>@endif<i class="ti ti-chevron-down" aria-hidden="true"></i></button>
                @if ($hasActiveFilters)<a href="{{ route('events.participants.index', $event) }}" class="btn btn-sm btn-link text-muted">Reset semua</a>@endif
            </div>
            <div id="participant-advanced-filter" class="collapse {{ $advancedFilterCount ? 'show' : '' }}"><div class="filter-advanced-panel"><div class="row g-3"><div class="col-md-6 col-lg-4"><label for="position_id" class="form-label">Jabatan</label><select id="position_id" name="position_id" class="form-select" form="participant-filter-form"><option value="">Semua jabatan</option>@foreach ($positions as $position)<option value="{{ $position->id }}" @selected((string) request('position_id') === (string) $position->id)>{{ $position->name }}</option>@endforeach</select></div></div></div></div>
            @if ($hasActiveFilters)<div class="active-filter-summary" aria-label="Filter aktif"><span class="active-filter-label">Filter aktif:</span>@if (request('participant_status'))<x-active-filter-chip label="Status" :value="$participantStatuses[request('participant_status')] ?? request('participant_status')" :url="route('events.participants.index', array_merge(['event' => $event->id], request()->except('participant_status', 'page')))" />@endif @if ($activeInstitution)<x-active-filter-chip label="Unit" :value="$activeInstitution" :url="route('events.participants.index', array_merge(['event' => $event->id], request()->except('institution_id', 'page')))" />@endif @if ($activePosition)<x-active-filter-chip label="Jabatan" :value="$activePosition" :url="route('events.participants.index', array_merge(['event' => $event->id], request()->except('position_id', 'page')))" />@endif</div>@endif
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <h5 class="mb-0">{{ $event->name }}</h5>
                <span class="text-muted small">Menampilkan {{ $participants->firstItem() ?? 0 }}-{{ $participants->lastItem() ?? 0 }} dari {{ $participants->total() }} peserta; total kegiatan {{ $event->participants_count }}</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4" style="width: 70px;">No</th>
                            <th>Pegawai</th>
                            <th>Unit & Jabatan</th>
                            <th>Status Peserta</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($participants as $participant)
                            <tr>
                                <td class="ps-4">{{ $participants->firstItem() + $loop->index }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $participant->employee?->full_name ?? '-' }}</div>
                                    <div class="data-meta">{{ $participant->employee?->employee_number ?? '-' }}</div>
                                </td>
                                <td>
                                    <div>{{ $participant->employee?->institution?->name ?? '-' }}</div>
                                    <div class="data-meta">{{ $participant->employee?->position?->name ?? '-' }}</div>
                                </td>
                                <td>
                                    <span class="badge {{ $participantStatusClasses[$participant->participant_status] ?? 'bg-light-secondary text-secondary' }}">
                                        {{ $participantStatuses[$participant->participant_status] ?? $participant->participant_status }}
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="table-actions">
                                        @if ($event->isDraft())
                                            <form method="POST" action="{{ route('event-participants.destroy', $participant) }}" data-confirm-title="Hapus Peserta?" data-confirm-message="Peserta akan dihapus dari kegiatan ini. Lanjutkan?">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-light-danger">
                                                    <i class="ti ti-trash"></i>
                                                    Hapus
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state">
                                        <div class="avtar avtar-l bg-light-secondary text-secondary">
                                            <i class="ti ti-users f-28"></i>
                                        </div>
                                        <h5 class="mb-1">{{ $hasActiveFilters ? 'Tidak ada peserta yang sesuai dengan filter.' : 'Belum ada peserta kegiatan.' }}</h5>
                                        <p class="text-muted {{ $hasActiveFilters ? 'mb-3' : 'mb-0' }}">{{ $hasActiveFilters ? 'Ubah atau reset filter untuk melihat peserta lainnya.' : 'Tambahkan peserta manual saat kegiatan masih draft.' }}</p>
                                        @if ($hasActiveFilters)<a href="{{ route('events.participants.index', $event) }}" class="btn btn-light-primary"><i class="ti ti-filter-off"></i> Reset Filter</a>@endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($participants->hasPages())
            <div class="card-footer">{{ $participants->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>
@endsection
