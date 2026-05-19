@extends('layouts.admin')

@section('title', 'Edit Pegawai | YAPISTA HRIS')

@section('content')
    <div class="page-header">
        <div class="page-block">
            <div class="row align-items-center">
                <div class="col-md-12">
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('employees.index') }}">Data Pegawai</a></li>
                        <li class="breadcrumb-item" aria-current="page">Edit</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="card page-intro-card">
        <div class="card-body">
            <h4 class="mb-1">Edit Pegawai</h4>
            <p class="mb-0 text-muted">Perbarui data dasar pegawai yang dikelola oleh Admin/HR.</p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Form Pegawai</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('employees.update', $employee) }}" enctype="multipart/form-data">
                @method('PUT')
                @include('employees._form')
            </form>
        </div>
    </div>
@endsection
