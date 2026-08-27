@php
    $currentStepNumber = array_search($step, array_keys($steps), true) + 1;
@endphp
<div class="profile-stepper mb-4" aria-label="Langkah pengisian profil">
    @unless ($employee->isVerified())
        <div class="employee-onboarding-step-summary d-md-none">
            <span>Langkah {{ $currentStepNumber }} dari {{ count($steps) }}</span>
            <strong>{{ $steps[$step]['short_label'] }}</strong>
        </div>
    @endunless
    <ul class="nav flex-nowrap {{ $employee->isVerified() ? '' : 'd-none d-md-flex' }}">
        @foreach ($steps as $slug => $definition)
            @php
                $section = $profileProgress['sections'][$slug] ?? null;
                $completed = $section['completed'] ?? false;
                $showCompleted = $completed && $slug !== 'administration';
            @endphp
            <li class="nav-item flex-fill">
                <a href="{{ route('pegawai.profile.wizard.show', $slug) }}" class="nav-link {{ $step === $slug ? 'active' : '' }} {{ $showCompleted ? 'is-complete' : '' }}" @if ($step === $slug) aria-current="step" @endif>
                    <span class="profile-step-number">@if ($showCompleted)<i class="ti ti-check" aria-hidden="true"></i>@else{{ $loop->iteration }}@endif</span>
                    <span class="fw-semibold">{{ $definition['short_label'] }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</div>
