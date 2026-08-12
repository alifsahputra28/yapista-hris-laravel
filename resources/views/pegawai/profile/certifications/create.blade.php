@extends('layouts.admin')

@section('title', 'Tambah Sertifikasi | YAPISTA HRIS')

@section('content')
    <x-page-header title="Tambah Sertifikasi" subtitle="Nama sertifikasi wajib diisi; data lainnya dapat dilengkapi nanti." :breadcrumbs="[['label' => 'Beranda', 'url' => route('pegawai.dashboard')], ['label' => 'Akun', 'url' => route('pegawai.profile.show')], ['label' => 'Tambah Sertifikasi']]">
        <x-slot:actions><a href="{{ route('pegawai.profile.show') }}" class="btn btn-light-secondary"><i class="ti ti-arrow-left" aria-hidden="true"></i> Kembali</a></x-slot:actions>
    </x-page-header>
    <div class="card"><div class="card-body"><form method="POST" action="{{ route('pegawai.profile.certifications.store') }}">@csrf @include('pegawai.profile.certifications._form')</form></div></div>
@endsection
