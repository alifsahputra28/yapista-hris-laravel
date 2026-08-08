@props([
    'title',
    'subtitle' => null,
    'breadcrumbs' => [],
    'badgeLabel' => null,
    'badgeClass' => 'bg-light-secondary text-secondary',
])

<header class="app-page-header">
    @if (count($breadcrumbs))
        <nav aria-label="Breadcrumb">
            <ol class="breadcrumb mb-2">
                @foreach (array_slice($breadcrumbs, -4) as $breadcrumb)
                    <li class="breadcrumb-item {{ $loop->last ? 'active' : '' }}" @if ($loop->last) aria-current="page" @endif>
                        @if (! $loop->last && filled($breadcrumb['url'] ?? null))
                            <a href="{{ $breadcrumb['url'] }}">{{ $breadcrumb['label'] }}</a>
                        @else
                            {{ $breadcrumb['label'] }}
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>
    @endif

    <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
        <div class="app-page-heading">
            <div class="d-flex align-items-center flex-wrap gap-2">
                <h1>{{ $title }}</h1>
                @if ($badgeLabel)
                    <span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
                @endif
            </div>
            @if ($subtitle)
                <p>{{ $subtitle }}</p>
            @endif
            @isset($meta)
                <div class="app-page-meta">{{ $meta }}</div>
            @endisset
        </div>

        @isset($actions)
            <div class="app-page-actions">{{ $actions }}</div>
        @endisset
    </div>
</header>
