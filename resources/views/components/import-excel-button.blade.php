@props([
    'target',
    'label' => 'Import Excel',
])

<button
    type="button"
    {{ $attributes->class(['btn btn-outline-success d-inline-flex align-items-center gap-2']) }}
    data-bs-toggle="modal"
    data-bs-target="{{ $target }}"
>
    <i class="ti ti-file-import" aria-hidden="true"></i>
    <span>{{ $label }}</span>
</button>
