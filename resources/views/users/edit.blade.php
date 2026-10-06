@extends('layouts.admin')

@section('title', 'Edit User | YAPISTA HRIS')

@section('content')
    <x-page-header
        title="Edit User"
        subtitle="Perbarui identitas akun, role, keterkaitan pegawai, dan status akses."
        :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Manajemen User', 'url' => route('users.index')], ['label' => 'Edit']]"
    />

    <div class="card">
        <div class="card-header"><h5 class="mb-0">Form User</h5></div>
        <div class="card-body">
            <form method="POST" action="{{ route('users.update', $managedUser) }}">
                @method('PUT')
                @include('users._form')
            </form>
        </div>
    </div>
@endsection
