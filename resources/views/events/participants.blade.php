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
    @endphp

    <div class="page-header">
        <div class="page-block">
            <div class="row align-items-center">
                <div class="col-md-12">
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('events.index') }}">Kegiatan</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('events.show', $event) }}">Detail</a></li>
                        <li class="breadcrumb-item" aria-current="page">Peserta</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="card page-intro-card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <h4 class="mb-1">Peserta Kegiatan</h4>
                    <p class="mb-0 text-muted">Kelola peserta untuk kegiatan {{ $event->name }}.</p>
                </div>
                <a href="{{ route('events.show', $event) }}" class="btn btn-light-secondary">Kembali</a>
            </div>
        </div>
    </div>

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

    <div class="card">
        <div class="card-header">
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <h5 class="mb-0">{{ $event->name }}</h5>
                <span class="text-muted small">{{ $event->participants->count() }} peserta</span>
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
                        @forelse ($event->participants as $participant)
                            <tr>
                                <td class="ps-4">{{ $loop->iteration }}</td>
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
                                            <form method="POST" action="{{ route('event-participants.destroy', $participant) }}" onsubmit="return confirm('Hapus peserta ini?')">
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
                                            <i class="ti ti-users-off f-28"></i>
                                        </div>
                                        <h5 class="mb-1">Belum ada peserta kegiatan.</h5>
                                        <p class="text-muted mb-0">Tambahkan peserta manual saat kegiatan masih draft.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
