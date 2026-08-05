<div class="profile-stepper mb-4" aria-label="Langkah pengisian profil">
    <ul class="nav nav-pills flex-nowrap gap-2">
        @foreach ($steps as $slug => $definition)
            @php
                $section = $profileProgress['sections'][$slug] ?? null;
                $completed = $section['completed'] ?? false;
                $partiallyCompleted = $section && ! $completed && $section['completed_items'] > 0;
            @endphp
            <li class="nav-item flex-fill">
                <a href="{{ route('pegawai.profile.wizard.show', $slug) }}" class="nav-link d-flex align-items-center gap-2 {{ $step === $slug ? 'active' : ($completed ? 'bg-light-success text-success' : 'bg-light-secondary text-secondary') }}" @if ($step === $slug) aria-current="step" @endif>
                    <span class="profile-step-number rounded-circle {{ $step === $slug ? 'bg-white text-primary' : ($completed ? 'bg-success text-white' : 'bg-white text-secondary') }}">@if ($completed)<i class="ti ti-check"></i>@else{{ $loop->iteration }}@endif</span>
                    <span><strong class="d-block">{{ $definition['short_label'] }}</strong><small>{{ $completed ? 'Lengkap' : ($partiallyCompleted ? 'Belum lengkap' : 'Belum diisi') }}</small></span>
                </a>
            </li>
        @endforeach
    </ul>
</div>
