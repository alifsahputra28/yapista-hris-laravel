@extends('layouts.admin')

@section('title', 'Tambah Jabatan | YAPISTA HRIS')

@section('content')
    <x-page-header title="Tambah Jabatan" subtitle="Tambahkan jabatan dan hubungkan dengan unit kerja yang sesuai." :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Jabatan', 'url' => route('positions.index')], ['label' => 'Tambah']]" />

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Form Jabatan</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('positions.store') }}">
                @include('positions._form')
            </form>
        </div>
    </div>
@endsection
