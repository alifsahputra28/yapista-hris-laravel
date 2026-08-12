@extends('layouts.admin')

@section('title', 'Edit Sertifikasi | YAPISTA HRIS')

@section('content')
    <x-page-header title="Edit Sertifikasi" subtitle="Perbarui sertifikasi atau kompetensi yang tersimpan." :breadcrumbs="[['label' => 'Beranda', 'url' => route('pegawai.dashboard')], ['label' => 'Akun', 'url' => route('pegawai.profile.show')], ['label' => 'Edit Sertifikasi']]">
        <x-slot:actions><a href="{{ route('pegawai.profile.show') }}" class="btn btn-light-secondary"><i class="ti ti-arrow-left" aria-hidden="true"></i> Kembali</a></x-slot:actions>
    </x-page-header>
    <div class="card"><div class="card-body"><form method="POST" action="{{ route('pegawai.profile.certifications.update', $certification) }}">@csrf @method('PUT') @include('pegawai.profile.certifications._form')</form></div></div>
@endsection
