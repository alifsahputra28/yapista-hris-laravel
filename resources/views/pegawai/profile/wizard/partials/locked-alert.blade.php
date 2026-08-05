<div class="alert {{ $employee->isVerified() ? 'alert-success' : 'alert-warning' }}">
    <i class="ti ti-lock me-1"></i>
    {{ $employee->isVerified() ? 'Profil telah terverifikasi. Perubahan data memerlukan proses review HR.' : 'Profil sedang menunggu pemeriksaan dan tidak dapat diubah.' }}
</div>
