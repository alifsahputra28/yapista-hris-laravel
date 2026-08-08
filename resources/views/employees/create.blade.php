@extends('layouts.admin')

@section('title', 'Tambah Pegawai | YAPISTA HRIS')

@section('content')
    <x-page-header
        title="Tambah Pegawai"
        subtitle="Input data dasar pegawai sebelum proses registrasi akun, dokumen, dan verifikasi."
        :breadcrumbs="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Data Pegawai', 'url' => route('employees.index')],
            ['label' => 'Tambah'],
        ]"
    />

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Form Pegawai</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('employees.store') }}" enctype="multipart/form-data">
                @include('employees._form')
            </form>
        </div>
    </div>
@endsection
