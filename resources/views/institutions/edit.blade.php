@extends('layouts.admin')

@section('title', 'Edit Unit Kerja | YAPISTA HRIS')

@section('content')
    <x-page-header title="Edit Unit Kerja" subtitle="Perbarui informasi unit kerja tanpa mengubah relasi data pegawai yang sudah berjalan." :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Unit Kerja', 'url' => route('institutions.index')], ['label' => 'Edit']]" />

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Form Unit Kerja</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('institutions.update', $institution) }}">
                @method('PUT')
                @include('institutions._form')
            </form>
        </div>
    </div>
@endsection
