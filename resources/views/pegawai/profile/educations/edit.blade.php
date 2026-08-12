@extends('layouts.admin')

@section('title', 'Edit Pendidikan | YAPISTA HRIS')

@section('content')
    <x-page-header title="Edit Riwayat Pendidikan" subtitle="Perbarui riwayat pendidikan yang tersimpan pada profil Anda." :breadcrumbs="[['label' => 'Beranda', 'url' => route('pegawai.dashboard')], ['label' => 'Akun', 'url' => route('pegawai.profile.show')], ['label' => 'Edit Pendidikan']]">
        <x-slot:actions><a href="{{ route('pegawai.profile.show') }}" class="btn btn-light-secondary"><i class="ti ti-arrow-left" aria-hidden="true"></i> Kembali</a></x-slot:actions>
    </x-page-header>
    <div class="card"><div class="card-body"><form method="POST" action="{{ route('pegawai.profile.educations.update', $education) }}">@csrf @method('PUT') @include('pegawai.profile.educations._form')</form></div></div>
@endsection
