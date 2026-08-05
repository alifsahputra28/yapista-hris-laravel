@php $statusClasses = ['active' => 'bg-light-success text-success', 'no_expiry' => 'bg-light-primary text-primary', 'expired' => 'bg-light-warning text-warning', 'inactive' => 'bg-light-secondary text-secondary']; @endphp
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2"><div><h5 class="mb-1">Riwayat Pendidikan</h5><p class="mb-0 text-muted">Tambahkan pendidikan dan tentukan satu pendidikan tertinggi.</p></div>@if ($editable)<a href="{{ route('pegawai.profile.educations.create') }}" class="btn btn-primary btn-sm"><i class="ti ti-plus"></i> Tambah Pendidikan</a>@endif</div>
    <div class="card-body p-0">
        @if ($employee->educations->isEmpty())
            <div class="empty-state"><i class="ti ti-school fs-1 text-muted"></i><h6 class="mt-3 mb-1">Belum ada riwayat pendidikan</h6><p class="text-muted mb-0">Data dapat ditambahkan kemudian.</p></div>
        @else
            <div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Pendidikan</th><th>Tahun</th><th>Tertinggi</th>@if ($editable)<th class="text-end">Aksi</th>@endif</tr></thead><tbody>
                @foreach ($employee->educations as $education)<tr><td><strong class="d-block">{{ $education->education_level_label }}</strong>{{ $education->institution_name }}<small class="text-muted d-block">Ijazah: {{ $education->masked_certificate_number }}</small></td><td>{{ $education->graduation_year ?? 'Belum diisi' }}</td><td><span class="badge {{ $education->is_highest ? 'bg-light-success text-success' : 'bg-light-secondary text-secondary' }}">{{ $education->is_highest ? 'Ya' : 'Tidak' }}</span></td>@if ($editable)<td><div class="table-actions"><a href="{{ route('pegawai.profile.educations.edit', $education) }}" class="btn btn-sm btn-outline-primary"><i class="ti ti-edit"></i> Edit</a><form method="POST" action="{{ route('pegawai.profile.educations.destroy', $education) }}" onsubmit="return confirm('Hapus data pendidikan ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit"><i class="ti ti-trash"></i><span class="visually-hidden">Hapus</span></button></form></div></td>@endif</tr>@endforeach
            </tbody></table></div>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2"><div><h5 class="mb-1">Sertifikasi dan Kompetensi</h5><p class="mb-0 text-muted">Sertifikasi bersifat opsional untuk progress tahap ini.</p></div>@if ($editable)<a href="{{ route('pegawai.profile.certifications.create') }}" class="btn btn-primary btn-sm"><i class="ti ti-plus"></i> Tambah Sertifikasi</a>@endif</div>
    <div class="card-body p-0">
        @if ($employee->certifications->isEmpty())
            <div class="empty-state"><i class="ti ti-certificate fs-1 text-muted"></i><h6 class="mt-3 mb-1">Belum ada sertifikasi</h6><p class="text-muted mb-0">Anda tetap dapat melanjutkan.</p></div>
        @else
            <div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Sertifikasi</th><th>Penerbit</th><th>Status</th>@if ($editable)<th class="text-end">Aksi</th>@endif</tr></thead><tbody>
                @foreach ($employee->certifications as $certification)<tr><td><strong class="d-block">{{ $certification->name }}</strong><small class="text-muted">Nomor: {{ $certification->masked_certificate_number }}</small></td><td>{{ $certification->issuer ?? 'Belum diisi' }}</td><td><span class="badge {{ $statusClasses[$certification->effective_status] ?? 'bg-light-secondary text-secondary' }}">{{ $certification->effective_status_label }}</span></td>@if ($editable)<td><div class="table-actions"><a href="{{ route('pegawai.profile.certifications.edit', $certification) }}" class="btn btn-sm btn-outline-primary"><i class="ti ti-edit"></i> Edit</a><form method="POST" action="{{ route('pegawai.profile.certifications.destroy', $certification) }}" onsubmit="return confirm('Hapus data sertifikasi ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit"><i class="ti ti-trash"></i><span class="visually-hidden">Hapus</span></button></form></div></td>@endif</tr>@endforeach
            </tbody></table></div>
        @endif
    </div>
</div>
<div class="d-flex justify-content-between gap-2"><a href="{{ route('pegawai.profile.wizard.show', 'family') }}" class="btn btn-light-secondary"><i class="ti ti-arrow-left"></i> Kembali</a><a href="{{ route('pegawai.profile.wizard.show', 'administration') }}" class="btn btn-primary">Lanjutkan <i class="ti ti-arrow-right"></i></a></div>
