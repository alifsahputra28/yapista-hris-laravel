@extends('layouts.admin')

@section('title', 'Tambah Anggota Keluarga | YAPISTA HRIS')

@section('content')
    <x-page-header title="Tambah Anggota Keluarga" subtitle="Nama lengkap dan hubungan wajib diisi. Data lainnya dapat dilengkapi nanti." :breadcrumbs="[['label' => 'Dashboard', 'url' => route('pegawai.dashboard')], ['label' => 'Profil Saya', 'url' => route('pegawai.profile.show')], ['label' => 'Tambah Anggota Keluarga']]">
        <x-slot:actions><a href="{{ route('pegawai.profile.show') }}" class="btn btn-light-secondary"><i class="ti ti-arrow-left" aria-hidden="true"></i> Kembali</a></x-slot:actions>
    </x-page-header>

    <div class="card"><div class="card-body">
        <form method="POST" action="{{ route('pegawai.profile.family-members.store') }}">
            @csrf
            @include('pegawai.profile.family-members._form')
        </form>
    </div></div>
@endsection
