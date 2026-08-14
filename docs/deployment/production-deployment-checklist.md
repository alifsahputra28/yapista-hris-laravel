# Production Deployment Checklist

Checklist ini tidak memberi izin deployment otomatis. Semua item **Blocker** wajib selesai sebelum go-live.

## 1. Release Approval

- [ ] Commit/tag release final telah ditetapkan dan working tree CI bersih.
- [ ] Stage 9 UAT/Pilot Internal telah disetujui.
- [ ] Tidak ada perubahan business logic di luar release scope.
- [ ] Maintenance window, owner, operator, dan rollback decision maker ditetapkan.
- [ ] **Blocker:** `composer audit --locked --no-dev` tidak lagi memiliki advisory yang melanggar kebijakan.
- [ ] **Blocker:** `npm audit --omit=dev` tidak lagi memiliki high/critical advisory.
- [ ] **Blocker:** enam file legacy/orphan pada public employee storage telah diklasifikasi dan dipindahkan/dihapus secara terotorisasi.
- [ ] **Blocker:** database backup/restore drill berhasil di environment terisolasi.

## 2. Infrastructure

- [ ] PHP 8.3+, ekstensi wajib, MySQL 8.x/InnoDB, dan web server tersedia.
- [ ] Document root menunjuk ke `public/`.
- [ ] Domain, DNS, HTTPS/TLS, dan redirect HTTP ke HTTPS berfungsi.
- [ ] Service account serta permission `storage/` dan `bootstrap/cache/` sudah minimum.
- [ ] Kapasitas disk dan alerting tersedia untuk database, storage, dan log.
- [ ] Firewall hanya membuka port yang diperlukan.

## 3. Secrets And Configuration

- [ ] `.env` produksi dibuat melalui secret store, bukan repository.
- [ ] `APP_ENV=production`, `APP_DEBUG=false`, dan `APP_URL=https://...`.
- [ ] `APP_TIMEZONE=Asia/Jakarta`.
- [ ] `APP_KEY` configured dan escrow; tidak dirotasi dari data sumber tanpa prosedur.
- [ ] `EMPLOYEE_NIK_LOOKUP_KEY` configured, berbeda dari `APP_KEY`, stabil, dan escrow.
- [ ] Database credential menggunakan account aplikasi least privilege.
- [ ] `SESSION_SECURE_COOKIE=true`, HTTP-only aktif, SameSite ditetapkan.
- [ ] SMTP produksi terkonfigurasi; mailer bukan `log`.
- [ ] Log level produksi tidak `debug` dan access log meredaksi token undangan.

## 4. Backup Gate

- [ ] Backup database pre-deployment selesai dan checksum valid.
- [ ] Backup `storage/app/private` selesai dan checksum valid.
- [ ] Legacy public employee files ikut diamankan sampai cleanup disetujui.
- [ ] Backup tersalin off-host dan terenkripsi.
- [ ] Restore point, waktu, dan operator dicatat.
- [ ] RPO/RTO telah disetujui pemilik bisnis.

## 5. Build Artifact

- [ ] `composer validate --no-check-publish` tidak memiliki error.
- [ ] `composer check-platform-reqs --no-dev` lulus.
- [ ] `composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction` lulus di clean build.
- [ ] `npm ci` dan `npm run build` lulus di clean build.
- [ ] `public/build/manifest.json` dan asset versioned tersedia.
- [ ] Tidak ada `.env`, dump, backup, private document, atau test artifact di artifact web.

## 6. Deploy

- [ ] Aktifkan maintenance mode dengan bypass secret yang tidak dicatat di log publik.
- [ ] Deploy release baru secara atomic (release directory/symlink), jangan overwrite source aktif parsial.
- [ ] Hubungkan shared persistent storage yang benar.
- [ ] Jalankan `php artisan migrate --force` hanya setelah backup dan review migration pending.
- [ ] Jangan menjalankan seeder, `migrate:fresh`, `db:wipe`, atau destructive command.
- [ ] Jalankan `php artisan optimize:clear`.
- [ ] Jalankan `php artisan config:cache`, `route:cache`, dan `view:cache`.
- [ ] Restart PHP-FPM/web workers agar opcode cache memakai release baru.
- [ ] Worker queue hanya direstart bila worker memang diaktifkan.

## 7. Data Safety Checks

- [ ] `php artisan migrate:status` menunjukkan seluruh migration expected Ran.
- [ ] `php artisan employee-security:verify-nik` tidak menemukan masalah.
- [ ] Dry-run dokumen/foto legacy ditinjau sebelum commit migration file.
- [ ] Private storage tidak dapat diakses melalui URL langsung.
- [ ] NUP tetap 10 digit dan QR tetap token acak/revocable.

## 8. Smoke Test

- [ ] Guest membuka `/` diarahkan ke login.
- [ ] Login super admin/HR, pegawai, dan panitia menuju landing sesuai role.
- [ ] Dashboard admin dan employee render tanpa error/asset 404.
- [ ] Employee list, exact NIK secure lookup, profile, dan verification dapat dibuka.
- [ ] Dokumen/foto terotorisasi dapat preview/download; direct URL ditolak.
- [ ] E-card menampilkan QR token aktif tanpa mengekspos token mentah.
- [ ] Scanner QR, manual attendance, duplicate handling, dan participant validation bekerja.
- [ ] Event/report/export/import template dapat diakses role yang benar.
- [ ] Password reset mengirim email melalui transport produksi.
- [ ] Guest/pegawai/panitia gagal mengakses route admin yang tidak berhak.

## 9. Post-Deploy Monitoring

- [ ] Nonaktifkan maintenance mode.
- [ ] Pantau HTTP 5xx, auth failures, queue failures, mail failures, database connection, latency, dan disk.
- [ ] Verifikasi tidak ada secret, NIK, QR token, atau private path pada log.
- [ ] Catat hasil smoke, waktu buka traffic, dan operator.
- [ ] Siapkan decision point rollback selama observation window.

## 10. Rollback

- [ ] Alihkan symlink/source ke release sebelumnya bila kegagalan hanya pada code/assets.
- [ ] Clear/cache ulang konfigurasi release sebelumnya dan restart worker.
- [ ] Jangan menjalankan migration rollback secara otomatis.
- [ ] Jika schema/data sudah berubah dan tidak backward-compatible, gunakan restore point sesuai runbook dengan approval incident commander.
- [ ] Restore database dan file sebagai satu set konsisten jika diperlukan.
- [ ] Ulangi smoke test dan dokumentasikan insiden.
