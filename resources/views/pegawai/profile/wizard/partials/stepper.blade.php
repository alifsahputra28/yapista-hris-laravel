<div class="profile-stepper mb-4" aria-label="Langkah pengisian profil">
    <ul class="nav flex-nowrap">
        @foreach ($steps as $slug => $definition)
            @php
                $section = $profileProgress['sections'][$slug] ?? null;
                $completed = $section['completed'] ?? false;
            @endphp
            <li class="nav-item flex-fill">
                <a href="{{ route('pegawai.profile.wizard.show', $slug) }}" class="nav-link {{ $step === $slug ? 'active' : '' }} {{ $completed ? 'is-complete' : '' }}" @if ($step === $slug) aria-current="step" @endif>
                    <span class="profile-step-number">@if ($completed)<i class="ti ti-check" aria-hidden="true"></i>@else{{ $loop->iteration }}@endif</span>
                    <span class="fw-semibold">{{ $definition['short_label'] }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</div>
