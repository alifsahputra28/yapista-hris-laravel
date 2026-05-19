@extends('layouts.admin')

@section('title', 'Tambah Pegawai | YAPISTA HRIS')

@section('content')
    <div class="page-header">
        <div class="page-block">
            <div class="row align-items-center">
                <div class="col-md-12">
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('employees.index') }}">Data Pegawai</a></li>
                        <li class="breadcrumb-item" aria-current="page">Tambah</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="card page-intro-card">
        <div class="card-body">
            <h4 class="mb-1">Tambah Pegawai</h4>
            <p class="mb-0 text-muted">Input data dasar pegawai sebelum proses registrasi akun, dokumen, dan verifikasi.</p>
        </div>
    </div>

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
