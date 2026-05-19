@extends('layouts.admin')

@section('title', 'Tambah Kegiatan | YAPISTA HRIS')

@section('content')
    <div class="page-header">
        <div class="page-block">
            <div class="row align-items-center">
                <div class="col-md-12">
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('events.index') }}">Kegiatan</a></li>
                        <li class="breadcrumb-item" aria-current="page">Tambah</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="card page-intro-card">
        <div class="card-body">
            <h4 class="mb-1">Tambah Kegiatan</h4>
            <p class="mb-0 text-muted">Buat kegiatan yayasan dan tentukan target peserta dari pegawai yang sudah terverifikasi.</p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Form Kegiatan</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('events.store') }}" class="js-target-form">
                @include('events._form')
            </form>
        </div>
    </div>
@endsection
