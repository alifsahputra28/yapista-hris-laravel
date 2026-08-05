@extends('layouts.admin')

@section('title', 'Tambah Pendidikan | YAPISTA HRIS')

@section('content')
    <div class="page-header"><div class="page-block"><div class="row align-items-center"><div class="col-md-12"><ul class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('pegawai.dashboard') }}">Dashboard</a></li><li class="breadcrumb-item"><a href="{{ route('pegawai.profile.show') }}">Profil Saya</a></li><li class="breadcrumb-item" aria-current="page">Tambah Pendidikan</li></ul></div></div></div></div>
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4"><div><h3 class="mb-1">Tambah Riwayat Pendidikan</h3><p class="mb-0 text-muted">Jenjang dan institusi wajib diisi untuk setiap record pendidikan.</p></div><a href="{{ route('pegawai.profile.show') }}" class="btn btn-light-secondary"><i class="ti ti-arrow-left"></i> Kembali</a></div>
    <div class="card"><div class="card-body"><form method="POST" action="{{ route('pegawai.profile.educations.store') }}">@csrf @include('pegawai.profile.educations._form')</form></div></div>
@endsection
