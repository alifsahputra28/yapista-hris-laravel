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
                    <div class="page-header-title">
                        <h5 class="m-b-10">Peserta Kegiatan</h5>
                    </div>

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
                <a href="{{ route('events.show', $event) }}" class="btn btn-light-secondary">Kembali</a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-borderless mb-0">
                    <thead>
                        <tr>
                            <th style="width: 70px;">No</th>
                            <th>Nama Pegawai</th>
                            <th>Nomor Pegawai</th>
                            <th>Unit Kerja</th>
                            <th>Jabatan</th>
                            <th>Status Peserta</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($event->participants as $participant)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $participant->employee?->full_name ?? '-' }}</td>
                                <td>{{ $participant->employee?->employee_number ?? '-' }}</td>
                                <td>{{ $participant->employee?->institution?->name ?? '-' }}</td>
                                <td>{{ $participant->employee?->position?->name ?? '-' }}</td>
                                <td>
                                    <span class="badge {{ $participantStatusClasses[$participant->participant_status] ?? 'bg-light-secondary text-secondary' }}">
                                        {{ $participantStatuses[$participant->participant_status] ?? $participant->participant_status }}
                                    </span>
                                </td>
                                <td class="text-end">
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
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">Belum ada peserta kegiatan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
