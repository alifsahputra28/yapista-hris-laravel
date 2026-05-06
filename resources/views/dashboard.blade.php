@extends('layouts.admin')

@section('title', 'Dashboard Admin | YAPISTA HRIS')

@section('content')
    <div class="page-header">
        <div class="page-block">
            <div class="row align-items-center">
                <div class="col-md-12">
                    <div class="page-header-title">
                        <h5 class="m-b-10">Dashboard</h5>
                    </div>

                    <ul class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="{{ route('dashboard') }}">Home</a>
                        </li>
                        <li class="breadcrumb-item" aria-current="page">Dashboard Admin</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="mb-2">Dashboard Admin HRIS YAPISTA</h4>
                    <p class="mb-0 text-muted">
                        Area awal untuk Super Admin dan HR Admin.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
