@extends('layouts.admin')

@section('title', 'Tambah Unit Kerja | YAPISTA HRIS')

@section('content')
    <div class="page-header">
        <div class="page-block">
            <div class="row align-items-center">
                <div class="col-md-12">
                    <div class="page-header-title">
                        <h5 class="m-b-10">Tambah Unit Kerja</h5>
                    </div>

                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('institutions.index') }}">Unit Kerja</a></li>
                        <li class="breadcrumb-item" aria-current="page">Tambah</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

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
