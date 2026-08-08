@extends('layouts.admin')

@section('title', 'Edit Pegawai | YAPISTA HRIS')

@section('content')
    <x-page-header
        title="Edit Pegawai"
        subtitle="Perbarui data dasar pegawai yang dikelola oleh Admin/HR."
        :breadcrumbs="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Data Pegawai', 'url' => route('employees.index')],
            ['label' => 'Edit'],
        ]"
    />

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Form Pegawai</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('employees.update', $employee) }}" enctype="multipart/form-data">
                @method('PUT')
                @include('employees._form')
            </form>
        </div>
    </div>
@endsection
