@extends('layouts.admin')

@section('title', 'Edit Profil | YAPISTA HRIS')

@section('content')
    <div class="page-header">
        <div class="page-block">
            <div class="row align-items-center">
                <div class="col-md-12">
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('pegawai.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('pegawai.profile.show') }}">Profil Saya</a></li>
                        <li class="breadcrumb-item" aria-current="page">Edit Profil</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h3 class="mb-1">Edit Profil Pegawai</h3>
            <p class="mb-0 text-muted">Perbarui identitas, kontak, dan alamat pribadi Anda.</p>
        </div>
        <a href="{{ route('pegawai.profile.show') }}" class="btn btn-light-secondary">
            <i class="ti ti-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="alert alert-light-primary border border-primary-subtle" role="alert">
        <i class="ti ti-info-circle me-1"></i>
        Lengkapi profil secara bertahap. Data yang belum tersedia dapat dikosongkan dan disimpan sebagai draft.
    </div>

    <form method="POST" action="{{ route('pegawai.profile.update') }}" enctype="multipart/form-data">
        @method('PUT')
        @include('pegawai.profile._form')
    </form>
@endsection
