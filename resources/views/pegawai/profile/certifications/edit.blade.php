@extends('layouts.admin')

@section('title', 'Edit Sertifikasi | YAPISTA HRIS')

@section('content')
    <div class="page-header"><div class="page-block"><div class="row align-items-center"><div class="col-md-12"><ul class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('pegawai.dashboard') }}">Dashboard</a></li><li class="breadcrumb-item"><a href="{{ route('pegawai.profile.show') }}">Profil Saya</a></li><li class="breadcrumb-item" aria-current="page">Edit Sertifikasi</li></ul></div></div></div></div>
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4"><div><h3 class="mb-1">Edit Sertifikasi</h3><p class="mb-0 text-muted">Perbarui sertifikasi atau kompetensi yang tersimpan.</p></div><a href="{{ route('pegawai.profile.show') }}" class="btn btn-light-secondary"><i class="ti ti-arrow-left"></i> Kembali</a></div>
    <div class="card"><div class="card-body"><form method="POST" action="{{ route('pegawai.profile.certifications.update', $certification) }}">@csrf @method('PUT') @include('pegawai.profile.certifications._form')</form></div></div>
@endsection
