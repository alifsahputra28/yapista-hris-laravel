@extends('layouts.admin')

@section('title', 'Tambah Kegiatan | YAPISTA HRIS')

@section('content')
    <x-page-header title="Tambah Kegiatan" subtitle="Buat kegiatan yayasan dan tentukan target peserta dari pegawai yang sudah terverifikasi." :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Kegiatan', 'url' => route('events.index')], ['label' => 'Tambah']]" />

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Form Kegiatan</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('events.store') }}" class="js-target-form">
                @include('events._form')
            </form>
        </div>
    </div>
@endsection
