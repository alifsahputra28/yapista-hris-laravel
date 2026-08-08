@extends('layouts.admin')

@section('title', ($steps[$step]['label'] ?? 'Lengkapi Profil').' | YAPISTA HRIS')

@section('content')
    @include('pegawai.profile.wizard.partials.header')

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('warning'))<div class="alert alert-warning">{{ session('warning') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @unless ($editable) @include('pegawai.profile.wizard.partials.locked-alert') @endunless

    @include('pegawai.profile.wizard.partials.stepper')

    <div class="profile-form-shell">
            @include('pegawai.profile.wizard.steps.'.$step)
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
