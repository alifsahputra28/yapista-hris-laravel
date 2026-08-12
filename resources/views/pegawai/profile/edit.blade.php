@extends('layouts.admin')

@section('title', 'Data Pribadi | YAPISTA HRIS')

@section('content')
    <x-page-header title="Data Pribadi" subtitle="Perbarui identitas, kontak, dan alamat pribadi Anda." :breadcrumbs="[['label' => 'Beranda', 'url' => route('pegawai.dashboard')], ['label' => 'Akun', 'url' => route('pegawai.profile.show')], ['label' => 'Data Pribadi']]">
        <x-slot:actions><a href="{{ route('pegawai.profile.show') }}" class="btn btn-light-secondary"><i class="ti ti-arrow-left" aria-hidden="true"></i> Kembali</a></x-slot:actions>
    </x-page-header>

    <div class="alert alert-light-primary border border-primary-subtle" role="alert">
        <i class="ti ti-info-circle me-1"></i>
        Lengkapi profil secara bertahap. Data yang belum tersedia dapat dikosongkan dan disimpan sebagai draft.
    </div>

    <form method="POST" action="{{ route('pegawai.profile.update') }}" enctype="multipart/form-data">
        @method('PUT')
        @include('pegawai.profile._form')
    </form>
@endsection
