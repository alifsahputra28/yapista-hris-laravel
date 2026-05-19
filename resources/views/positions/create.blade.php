@extends('layouts.admin')

@section('title', 'Tambah Jabatan | YAPISTA HRIS')

@section('content')
    <div class="page-header">
        <div class="page-block">
            <div class="row align-items-center">
                <div class="col-md-12">
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('positions.index') }}">Jabatan</a></li>
                        <li class="breadcrumb-item" aria-current="page">Tambah</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="card page-intro-card">
        <div class="card-body">
            <h4 class="mb-1">Tambah Jabatan</h4>
            <p class="mb-0 text-muted">Tambahkan jabatan dan hubungkan dengan unit kerja yang sesuai.</p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Form Jabatan</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('positions.store') }}">
                @include('positions._form')
            </form>
        </div>
    </div>
@endsection
