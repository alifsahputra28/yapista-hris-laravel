@extends('layouts.admin')

@section('title', 'Edit Jabatan | YAPISTA HRIS')

@section('content')
    <x-page-header title="Edit Jabatan" subtitle="Perbarui nama, tipe, status, dan unit kerja untuk jabatan ini." :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Jabatan', 'url' => route('positions.index')], ['label' => 'Edit']]" />

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Form Jabatan</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('positions.update', $position) }}">
                @method('PUT')
                @include('positions._form')
            </form>
        </div>
    </div>
@endsection
