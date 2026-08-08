@extends('layouts.admin')

@section('title', 'Tambah Pendidikan | YAPISTA HRIS')

@section('content')
    <x-page-header title="Tambah Riwayat Pendidikan" subtitle="Jenjang dan institusi wajib diisi untuk setiap record pendidikan." :breadcrumbs="[['label' => 'Dashboard', 'url' => route('pegawai.dashboard')], ['label' => 'Profil Saya', 'url' => route('pegawai.profile.show')], ['label' => 'Tambah Pendidikan']]">
        <x-slot:actions><a href="{{ route('pegawai.profile.show') }}" class="btn btn-light-secondary"><i class="ti ti-arrow-left" aria-hidden="true"></i> Kembali</a></x-slot:actions>
    </x-page-header>
    <div class="card"><div class="card-body"><form method="POST" action="{{ route('pegawai.profile.educations.store') }}">@csrf @include('pegawai.profile.educations._form')</form></div></div>
@endsection
