@props([
    'label',
    'value',
    'url',
    'removeLabel' => null,
])

<a
    href="{{ $url }}"
    class="active-filter-chip"
    aria-label="{{ $removeLabel ?? 'Hapus filter '.$label }}"
>
    <span>{{ $label }}: <strong>{{ $value }}</strong></span>
    <i class="ti ti-x" aria-hidden="true"></i>
</a>
