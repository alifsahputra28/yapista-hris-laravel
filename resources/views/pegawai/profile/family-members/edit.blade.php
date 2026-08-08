@extends('layouts.admin')

@section('title', 'Edit Anggota Keluarga | YAPISTA HRIS')

@section('content')
    <x-page-header title="Edit Anggota Keluarga" subtitle="Perbarui data keluarga yang tersimpan pada profil Anda." :breadcrumbs="[['label' => 'Dashboard', 'url' => route('pegawai.dashboard')], ['label' => 'Profil Saya', 'url' => route('pegawai.profile.show')], ['label' => 'Edit Anggota Keluarga']]">
        <x-slot:actions><a href="{{ route('pegawai.profile.show') }}" class="btn btn-light-secondary"><i class="ti ti-arrow-left" aria-hidden="true"></i> Kembali</a></x-slot:actions>
    </x-page-header>

    <div class="card"><div class="card-body">
        <form method="POST" action="{{ route('pegawai.profile.family-members.update', $familyMember) }}">
            @csrf
            @method('PUT')
            @include('pegawai.profile.family-members._form')
        </form>
    </div></div>
@endsection
