@extends('layouts.admin')

@section('title', 'Edit Data Administrasi | YAPISTA HRIS')

@section('content')
    @php
        $taxStatuses = \App\Models\EmployeeAdministrativeDetail::TAX_STATUSES;
        $bpjsStatuses = \App\Models\EmployeeAdministrativeDetail::BPJS_STATUSES;
        $ptkpStatuses = \App\Models\EmployeeAdministrativeDetail::PTKP_STATUSES;
        $nikUsedAsTaxIdValue = old(
            'nik_used_as_tax_id',
            is_null($administrativeDetail->nik_used_as_tax_id) ? '' : ($administrativeDetail->nik_used_as_tax_id ? '1' : '0'),
        );
    @endphp

    <x-page-header title="Edit Data Administrasi" subtitle="Kelola informasi rekening, pajak, dan kepesertaan BPJS." :breadcrumbs="[['label' => 'Beranda', 'url' => route('pegawai.dashboard')], ['label' => 'Akun', 'url' => route('pegawai.profile.show')], ['label' => 'Data Administrasi']]">
        <x-slot:actions><a href="{{ route('pegawai.profile.show') }}" class="btn btn-light-secondary"><i class="ti ti-arrow-left" aria-hidden="true"></i> Kembali</a></x-slot:actions>
    </x-page-header>

    <div class="alert alert-light-primary">Data ini bersifat sensitif dan hanya digunakan untuk administrasi kepegawaian. Data dapat dilengkapi secara bertahap.</div>

    <form method="POST" action="{{ route('pegawai.profile.administrative-details.update') }}">
        @csrf
        @method('PUT')

        <div class="card">
            <div class="card-header"><h5 class="mb-0">Rekening Bank</h5></div>
            <div class="card-body"><div class="row g-3">
                <div class="col-md-6">
                    <label for="bank_name" class="form-label">Nama Bank</label>
                    <input id="bank_name" type="text" name="bank_name" value="{{ old('bank_name', $administrativeDetail->bank_name) }}" maxlength="100" class="form-control @error('bank_name') is-invalid @enderror">
                    @error('bank_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="bank_account_number" class="form-label">Nomor Rekening</label>
                    <input id="bank_account_number" type="text" name="bank_account_number" value="{{ old('bank_account_number', $administrativeDetail->bank_account_number) }}" maxlength="30" inputmode="numeric" autocomplete="off" class="form-control @error('bank_account_number') is-invalid @enderror">
                    @error('bank_account_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label for="bank_account_holder" class="form-label">Nama Pemilik Rekening</label>
                    <input id="bank_account_holder" type="text" name="bank_account_holder" value="{{ old('bank_account_holder', $administrativeDetail->bank_account_holder) }}" maxlength="255" class="form-control @error('bank_account_holder') is-invalid @enderror">
                    @error('bank_account_holder')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div></div>
        </div>

        <div class="card">
            <div class="card-header"><h5 class="mb-0">Perpajakan</h5></div>
            <div class="card-body"><div class="row g-3">
                <div class="col-md-6">
                    <label for="tax_status" class="form-label">Status Pajak</label>
                    <select id="tax_status" name="tax_status" class="form-select @error('tax_status') is-invalid @enderror">
                        <option value="">Belum dipilih</option>
                        @foreach ($taxStatuses as $value => $label)<option value="{{ $value }}" @selected(old('tax_status', $administrativeDetail->tax_status) === $value)>{{ $label }}</option>@endforeach
                    </select>
                    @error('tax_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="tax_identification_number" class="form-label">Nomor Identitas Pajak</label>
                    <input id="tax_identification_number" type="text" name="tax_identification_number" value="{{ old('tax_identification_number', $administrativeDetail->tax_identification_number) }}" maxlength="24" inputmode="numeric" autocomplete="off" class="form-control @error('tax_identification_number') is-invalid @enderror">
                    <div class="form-text">Pemisah spasi, titik, dan tanda hubung akan dihapus saat disimpan.</div>
                    @error('tax_identification_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="nik_used_as_tax_id" class="form-label">NIK Digunakan sebagai Identitas Pajak</label>
                    <select id="nik_used_as_tax_id" name="nik_used_as_tax_id" class="form-select @error('nik_used_as_tax_id') is-invalid @enderror">
                        <option value="">Belum dijawab</option>
                        <option value="1" @selected((string) $nikUsedAsTaxIdValue === '1')>Ya</option>
                        <option value="0" @selected((string) $nikUsedAsTaxIdValue === '0')>Tidak</option>
                    </select>
                    @error('nik_used_as_tax_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="ptkp_status" class="form-label">Status PTKP</label>
                    <select id="ptkp_status" name="ptkp_status" class="form-select @error('ptkp_status') is-invalid @enderror">
                        <option value="">Belum dipilih</option>
                        @foreach ($ptkpStatuses as $status)<option value="{{ $status }}" @selected(old('ptkp_status', $administrativeDetail->ptkp_status) === $status)>{{ $status }}</option>@endforeach
                    </select>
                    @error('ptkp_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div></div>
        </div>

        <div class="card">
            <div class="card-header"><h5 class="mb-0">BPJS Kesehatan</h5></div>
            <div class="card-body"><div class="row g-3">
                <div class="col-md-6">
                    <label for="bpjs_health_status" class="form-label">Status Kepesertaan</label>
                    <select id="bpjs_health_status" name="bpjs_health_status" class="form-select @error('bpjs_health_status') is-invalid @enderror">
                        <option value="">Belum dipilih</option>
                        @foreach ($bpjsStatuses as $value => $label)<option value="{{ $value }}" @selected(old('bpjs_health_status', $administrativeDetail->bpjs_health_status) === $value)>{{ $label }}</option>@endforeach
                    </select>
                    @error('bpjs_health_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="bpjs_health_number" class="form-label">Nomor BPJS Kesehatan</label>
                    <input id="bpjs_health_number" type="text" name="bpjs_health_number" value="{{ old('bpjs_health_number', $administrativeDetail->bpjs_health_number) }}" maxlength="20" inputmode="numeric" autocomplete="off" class="form-control @error('bpjs_health_number') is-invalid @enderror">
                    @error('bpjs_health_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div></div>
        </div>

        <div class="card">
            <div class="card-header"><h5 class="mb-0">BPJS Ketenagakerjaan</h5></div>
            <div class="card-body"><div class="row g-3">
                <div class="col-md-6">
                    <label for="bpjs_employment_status" class="form-label">Status Kepesertaan</label>
                    <select id="bpjs_employment_status" name="bpjs_employment_status" class="form-select @error('bpjs_employment_status') is-invalid @enderror">
                        <option value="">Belum dipilih</option>
                        @foreach ($bpjsStatuses as $value => $label)<option value="{{ $value }}" @selected(old('bpjs_employment_status', $administrativeDetail->bpjs_employment_status) === $value)>{{ $label }}</option>@endforeach
                    </select>
                    @error('bpjs_employment_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="bpjs_employment_number" class="form-label">Nomor KPJ/BPJS Ketenagakerjaan</label>
                    <input id="bpjs_employment_number" type="text" name="bpjs_employment_number" value="{{ old('bpjs_employment_number', $administrativeDetail->bpjs_employment_number) }}" maxlength="20" inputmode="numeric" autocomplete="off" class="form-control @error('bpjs_employment_number') is-invalid @enderror">
                    @error('bpjs_employment_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div></div>
        </div>

        <div class="d-flex flex-wrap gap-2 mb-4">
            <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i> Simpan Data Administrasi</button>
            <a href="{{ route('pegawai.profile.show') }}" class="btn btn-light-secondary">Batal</a>
        </div>
    </form>
@endsection
