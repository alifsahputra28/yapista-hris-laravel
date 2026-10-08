@extends('layouts.admin')

@section('title', 'Options | YAPISTA HRIS')

@section('content')
    <x-page-header
        title="Options"
        subtitle="Pengaturan master sistem HRIS. Kode sistem bersifat tetap dan digunakan sebagai acuan seluruh modul."
        :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Organisasi'], ['label' => 'Options']]"
    />

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-1">Level Unit Kerja</h5>
            <p class="text-muted small mb-0">Klasifikasi hierarki unit organisasi, bukan jenjang jabatan.</p>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th class="ps-4" style="width: 90px;">Kode</th><th>Nama dan Deskripsi</th><th>Contoh</th><th class="text-end pe-4">Status</th></tr></thead>
                    <tbody>
                        @foreach ($unitLevels as $code => $option)
                            <tr>
                                <td class="ps-4"><span class="badge bg-light-primary text-primary">{{ $code }}</span></td>
                                <td><div class="fw-semibold">{{ $option['name'] }}</div><div class="text-muted small">{{ $option['description'] }}</div></td>
                                <td class="small text-muted">{{ implode(', ', $option['examples']) }}</td>
                                <td class="text-end pe-4"><span class="badge bg-light-success text-success">Aktif</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-1">Tipe Jabatan</h5>
            <p class="text-muted small mb-0">Klasifikasi fungsi jabatan dalam organisasi.</p>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th class="ps-4" style="width: 90px;">Kode</th><th>Nama dan Deskripsi</th><th>Contoh</th><th class="text-end pe-4">Status</th></tr></thead>
                    <tbody>
                        @foreach ($positionTypes as $code => $option)
                            <tr>
                                <td class="ps-4"><span class="badge bg-light-info text-info">{{ $code }}</span></td>
                                <td><div class="fw-semibold">{{ $option['name'] }}</div><div class="text-muted small">{{ $option['description'] }}</div></td>
                                <td class="small text-muted">{{ implode(', ', $option['examples']) }}</td>
                                <td class="text-end pe-4"><span class="badge bg-light-success text-success">Aktif</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
