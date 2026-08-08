@props([
    'alt' => 'YAPISTA HRIS',
    'imageClass' => '',
])

<span {{ $attributes->class(['app-logo']) }}>
    <img
        src="{{ asset('assets/images/logo-yapista-hris.png') }}"
        alt="{{ $alt }}"
        class="app-logo-image {{ $imageClass }}"
        onerror="this.hidden = true; this.nextElementSibling.hidden = false;"
    >
    <span class="app-logo-fallback" hidden>YAPISTA HRIS</span>
</span>
