@extends('layouts.admin')

@section('title', 'Tambah Unit Kerja | YAPISTA HRIS')

@section('content')
    <x-page-header title="Tambah Unit Kerja" subtitle="Tambahkan unit kerja atau lembaga yang akan dipakai pada data jabatan dan pegawai." :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Unit Kerja', 'url' => route('institutions.index')], ['label' => 'Tambah']]" />

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Form Unit Kerja</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('institutions.store') }}">
                @include('institutions._form')
            </form>
        </div>
    </div>
@endsection
