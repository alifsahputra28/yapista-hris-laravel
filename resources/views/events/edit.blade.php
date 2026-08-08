@extends('layouts.admin')

@section('title', 'Edit Kegiatan | YAPISTA HRIS')

@section('content')
    <x-page-header title="Edit Kegiatan" subtitle="Perbarui informasi kegiatan draft dan generate ulang peserta jika target berubah." :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Kegiatan', 'url' => route('events.index')], ['label' => 'Detail', 'url' => route('events.show', $event)], ['label' => 'Edit']]" />

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Form Kegiatan</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('events.update', $event) }}" class="js-target-form">
                @method('PUT')
                @include('events._form')
            </form>
        </div>
    </div>
@endsection
