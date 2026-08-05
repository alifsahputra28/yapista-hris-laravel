@extends('layouts.admin')

@section('title', ($steps[$step]['label'] ?? 'Lengkapi Profil').' | YAPISTA HRIS')

@push('styles')
<style>
    .profile-stepper { overflow-x: auto; scrollbar-width: thin; }
    .profile-stepper .nav { min-width: 760px; }
    .profile-stepper .nav-link { min-height: 62px; text-align: left; white-space: nowrap; }
    .profile-step-number { width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 28px; }
    @media (min-width: 992px) { .profile-wizard-sidebar { position: sticky; top: 90px; } }
</style>
@endpush

@section('content')
    @include('pegawai.profile.wizard.partials.header')

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @unless ($editable) @include('pegawai.profile.wizard.partials.locked-alert') @endunless

    @include('pegawai.profile.wizard.partials.stepper')

    <div class="row g-4">
        <div class="col-lg-8 order-2 order-lg-1">
            @include('pegawai.profile.wizard.steps.'.$step)
        </div>
        <div class="col-lg-4 order-1 order-lg-2">
            <div class="profile-wizard-sidebar">
                @include('pegawai.profile.wizard.partials.employment-summary')
                @include('pegawai.profile.wizard.partials.progress-card')
            </div>
        </div>
    </div>
@endsection

@push('page-scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const sameWhatsapp = document.getElementById('same_as_phone');
    const phone = document.getElementById('phone');
    const whatsapp = document.getElementById('whatsapp_number');
    if (sameWhatsapp && phone && whatsapp) {
        sameWhatsapp.addEventListener('change', function () {
            if (this.checked) whatsapp.value = phone.value;
        });
    }

    const sameAddress = document.getElementById('domicile_same_as_identity');
    const identityAddress = document.getElementById('identity_address');
    const domicileAddress = document.getElementById('address');
    if (sameAddress && identityAddress && domicileAddress) {
        const syncAddress = function () {
            if (sameAddress.checked) {
                domicileAddress.value = identityAddress.value;
                domicileAddress.readOnly = true;
            } else {
                domicileAddress.readOnly = false;
            }
        };
        sameAddress.addEventListener('change', syncAddress);
        identityAddress.addEventListener('input', syncAddress);
        syncAddress();
    }

    document.querySelectorAll('[data-wizard-form]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (form.dataset.submitting === 'true') {
                event.preventDefault();
                return;
            }
            form.dataset.submitting = 'true';
        });
    });
});
</script>
@endpush
