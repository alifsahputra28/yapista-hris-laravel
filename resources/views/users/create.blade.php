@extends('layouts.admin')

@section('title', 'Tambah User | YAPISTA HRIS')

@section('content')
    <x-page-header
        title="Tambah User"
        subtitle="Buat akun autentikasi dan hubungkan dengan data pegawai bila diperlukan."
        :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Manajemen User', 'url' => route('users.index')], ['label' => 'Tambah']]"
    />

    <div class="card">
        <div class="card-header"><h5 class="mb-0">Form User</h5></div>
        <div class="card-body">
            <form method="POST" action="{{ route('users.store') }}">
                @include('users._form')
            </form>
        </div>
    </div>
@endsection
