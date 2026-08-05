<div class="card">
    <div class="card-header"><h5 class="mb-0">Status Kelengkapan</h5></div>
    <div class="card-body">
        @foreach ($profileProgress['sections'] as $slug => $section)
            <div class="d-flex justify-content-between align-items-center gap-2 {{ $loop->last ? '' : 'mb-3' }}">
                <a href="{{ route('pegawai.profile.wizard.show', $slug) }}" class="text-body">{{ $section['label'] }}</a>
                <span class="badge {{ $section['completed'] ? 'bg-light-success text-success' : 'bg-light-secondary text-secondary' }}">{{ $section['completed'] ? 'Lengkap' : $section['percentage'].'%' }}</span>
            </div>
        @endforeach
        <div class="border-top mt-3 pt-3 d-flex justify-content-between"><span>Bagian lengkap</span><strong>{{ $profileProgress['completed_sections'] }}/{{ $profileProgress['total_sections'] }}</strong></div>
    </div>
</div>
