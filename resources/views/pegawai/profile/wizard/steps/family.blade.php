@php $display = fn ($value) => filled($value) ? $value : 'Belum diisi'; @endphp
<div class="card">
    <div class="card-header"><h5 class="mb-1">Kontak Darurat</h5><p class="mb-0 text-muted">Kontak yang dapat dihubungi ketika terjadi keadaan darurat.</p></div>
    <div class="card-body">
        @if ($editable)
            <form method="POST" action="{{ route('pegawai.profile.wizard.family.update') }}" data-wizard-form>
                @csrf @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6"><label for="emergency_contact_name" class="form-label">Nama Kontak</label><input id="emergency_contact_name" name="emergency_contact_name" type="text" value="{{ old('emergency_contact_name', $employee->emergency_contact_name) }}" maxlength="255" class="form-control @error('emergency_contact_name') is-invalid @enderror">@error('emergency_contact_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-6"><label for="emergency_contact_relationship" class="form-label">Hubungan</label><input id="emergency_contact_relationship" name="emergency_contact_relationship" type="text" value="{{ old('emergency_contact_relationship', $employee->emergency_contact_relationship) }}" maxlength="100" class="form-control @error('emergency_contact_relationship') is-invalid @enderror">@error('emergency_contact_relationship')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-6"><label for="emergency_contact_phone" class="form-label">Nomor HP</label><input id="emergency_contact_phone" name="emergency_contact_phone" type="text" value="{{ old('emergency_contact_phone', $employee->emergency_contact_phone) }}" maxlength="30" class="form-control @error('emergency_contact_phone') is-invalid @enderror">@error('emergency_contact_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-12"><label for="emergency_contact_address" class="form-label">Alamat Kontak</label><textarea id="emergency_contact_address" name="emergency_contact_address" rows="3" maxlength="2000" class="form-control @error('emergency_contact_address') is-invalid @enderror">{{ old('emergency_contact_address', $employee->emergency_contact_address) }}</textarea>@error('emergency_contact_address')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                </div>
                <div class="d-flex flex-wrap justify-content-end gap-2 mt-4"><button name="wizard_action" value="stay" class="btn btn-outline-primary" type="submit">Simpan Draft</button><button name="wizard_action" value="next" class="btn btn-primary" type="submit">Simpan &amp; Lanjutkan <i class="ti ti-arrow-right"></i></button></div>
            </form>
        @else
<div class="row g-4"><div class="col-md-6"><small class="text-muted d-block mb-1">Nama Kontak</small>{{ $display($employee->emergency_contact_name) }}</div><div class="col-md-6"><small class="text-muted d-block mb-1">Hubungan</small>{{ $display($employee->emergency_contact_relationship) }}</div><div class="col-md-6"><small class="text-muted d-block mb-1">Nomor HP</small>{{ $display($employee->emergency_contact_phone) }}</div><div class="col-12"><small class="text-muted d-block mb-1">Alamat</small>{{ $display($employee->emergency_contact_address) }}</div></div>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2"><div><h5 class="mb-1">Anggota Keluarga</h5><p class="mb-0 text-muted">Data pasangan dan anggota keluarga lainnya.</p></div>@if ($editable)<a href="{{ route('pegawai.profile.family-members.create') }}" class="btn btn-primary btn-sm"><i class="ti ti-plus"></i> Tambah Anggota</a>@endif</div>
    <div class="card-body p-0">
        @if ($employee->familyMembers->isEmpty())
            <div class="empty-state"><i class="ti ti-users fs-1 text-muted"></i><h6 class="mt-3 mb-1">Belum ada data keluarga</h6><p class="text-muted mb-0">Anda tetap dapat melanjutkan ke langkah berikutnya.</p></div>
        @else
            <div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Nama</th><th>Hubungan</th><th>Tanggungan</th>@if ($editable)<th class="text-end">Aksi</th>@endif</tr></thead><tbody>
                @foreach ($employee->familyMembers as $familyMember)<tr><td><strong class="d-block">{{ $familyMember->full_name }}</strong><small class="text-muted">NIK: {{ $familyMember->masked_nik }}</small></td><td>{{ $familyMember->relationship_label }}</td><td>{{ $familyMember->is_dependent ? 'Ya' : 'Tidak' }}</td>@if ($editable)<td><div class="table-actions"><a href="{{ route('pegawai.profile.family-members.edit', $familyMember) }}" class="btn btn-sm btn-outline-primary"><i class="ti ti-edit"></i> Edit</a><form method="POST" action="{{ route('pegawai.profile.family-members.destroy', $familyMember) }}" onsubmit="return confirm('Hapus data anggota keluarga ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit"><i class="ti ti-trash"></i><span class="visually-hidden">Hapus</span></button></form></div></td>@endif</tr>@endforeach
            </tbody></table></div>
        @endif
    </div>
</div>
<div class="d-flex justify-content-between gap-2"><a href="{{ route('pegawai.profile.wizard.show', 'contact-address') }}" class="btn btn-light-secondary"><i class="ti ti-arrow-left"></i> Kembali</a><a href="{{ route('pegawai.profile.wizard.show', 'education') }}" class="btn btn-primary">Lanjutkan <i class="ti ti-arrow-right"></i></a></div>
