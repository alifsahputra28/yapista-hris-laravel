@extends('layouts.admin')

@section('title', 'Buat Akun Login | YAPISTA HRIS')

@section('content')
    <x-page-header
        title="Buat Akun Login"
        subtitle="Buat akun autentikasi untuk pegawai yang membutuhkan akses HRIS."
        :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Data Pegawai', 'url' => route('employees.index')], ['label' => 'Buat Akun Login']]"
    />

    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('employees.account.store', $employee) }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nama Pegawai</label>
                        <input type="text" class="form-control" value="{{ $employee->full_name }}" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">NUP</label>
                        <input type="text" class="form-control" value="{{ $employee->employee_number ?: 'Belum tersedia' }}" readonly>
                    </div>
                    <div class="col-md-6">
                        <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                        <select id="role" name="role" class="form-select" required>
                            @foreach ($roleLabels as $value => $label)
                                <option value="{{ $value }}" @selected(old('role', 'pegawai') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email Login <span class="text-muted">(opsional jika NUP valid)</span></label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" autocomplete="email">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
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
                </div>
                <div class="d-flex flex-column flex-sm-row gap-2 mt-4">
                    <button type="submit" class="btn btn-primary"><i class="ti ti-user-plus" aria-hidden="true"></i> Buat Akun</button>
                    <a href="{{ route('employees.show', $employee) }}" class="btn btn-light-secondary">Kembali</a>
                </div>
            </form>
        </div>
    </div>
@endsection
