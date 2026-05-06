@extends('layouts.admin')

@section('title', 'Edit Biodata | YAPISTA HRIS')

@section('content')
    <div class="page-header">
        <div class="page-block">
            <div class="row align-items-center">
                <div class="col-md-12">
                    <div class="page-header-title">
                        <h5 class="m-b-10">Edit Biodata</h5>
                    </div>

                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('pegawai.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('pegawai.profile.show') }}">Profil Saya</a></li>
                        <li class="breadcrumb-item" aria-current="page">Edit Biodata</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Form Biodata</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('pegawai.profile.update') }}" enctype="multipart/form-data">
                @method('PUT')
                @include('pegawai.profile._form')
            </form>
        </div>
    </div>
@endsection
