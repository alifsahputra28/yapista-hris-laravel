@extends('layouts.admin')

@section('title', 'Edit Profil | YAPISTA HRIS')

@section('content')
    <x-page-header title="Edit Profil Pegawai" subtitle="Perbarui identitas, kontak, dan alamat pribadi Anda." :breadcrumbs="[['label' => 'Dashboard', 'url' => route('pegawai.dashboard')], ['label' => 'Profil Saya', 'url' => route('pegawai.profile.show')], ['label' => 'Edit Profil']]">
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
