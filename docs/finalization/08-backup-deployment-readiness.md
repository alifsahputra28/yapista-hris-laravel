# YAPISTA HRIS Stage 8 - Backup & Deployment Readiness

## 1. Scope And Decision

Tahap 8 hanya mengaudit kesiapan backup/deployment, menguji prosedur aman, dan membuat dokumentasi operasional. Tidak ada deployment production, migration, seeder, package update, perubahan business logic, atau perubahan data development.

**Keputusan:** **NOT READY FOR PRODUCTION / READY TO CONTINUE TO STAGE 9 UAT WITH RESTRICTIONS**.

UAT lokal/internal dapat dilanjutkan menggunakan baseline fungsional yang lulus. Go-live diblokir oleh dependency advisories, file legacy/orphan pada public storage, dan database restore drill yang belum dapat dilakukan pada host audit.

## 2. Baseline

| Item | Hasil aktual |
|---|---|
| Branch | `main` |
| HEAD | `4064778` |
| Working tree sebelum Tahap 8 | Clean |
| Laravel | 13.7.0 |
| PHP | 8.3.16 |
| Composer | 2.8.5 |
| Node/npm | 22.13.1 / 10.9.2 |
| Database | MySQL 8.4.3, 22 tabel, seluruh tabel engine InnoDB |
| Database size audit | 1.14 MB |
| Routes | 122 |
| Migration | 26 Ran, 0 Pending |
| Baseline test | 297 passed, 2470 assertions, 0 failed, 0 skipped |
| Baseline build | PASS, Vite 8.0.10 |

Environment development saat audit: `local`, debug aktif, database/cache/session/queue menggunakan MySQL/database driver, mailer `log`, filesystem default private-local. Kondisi tersebut bukan konfigurasi produksi.

## 3. Production Runtime Audit

- `composer check-platform-reqs --no-dev`: PASS.
- Semua ekstensi platform lockfile tersedia; `pdo_mysql`, `intl`, `curl`, `bcmath`, `gd`, `zip`, dan `openssl` juga tersedia.
- MySQL 8.4.3 kompatibel dengan 26 migration aktif dan seluruh tabel audit menggunakan InnoDB.
- `public/.htaccess` menyediakan front-controller rewrite dan mematikan directory indexes untuk Apache.
- Production web root wajib `public/`; Nginx/Apache, domain, TLS, dan service account final belum dipilih.
- `composer install --dry-run --no-dev --optimize-autoloader`: PASS.
- `npm ci --dry-run --ignore-scripts`: PASS; menunjukkan 36 optional platform packages yang akan diselesaikan pada clean install.

## 4. Configuration And Secrets

- `APP_KEY`: configured pada development; nilainya tidak dicetak.
- `EMPLOYEE_NIK_LOOKUP_KEY`: configured dan digunakan via `config/security.php`; nilainya tidak dicetak.
- Audit tidak menemukan pemanggilan `env()` aktif di luar file `config/`.
- `.env.example` memuat placeholder APP key, database, mail, session, dan NIK lookup key, tetapi default-nya untuk local development (`APP_DEBUG=true`, SQLite, mail log, secure cookie false).
- Produksi wajib memakai environment terpisah: debug false, HTTPS URL, secure cookie true, MySQL, SMTP nyata, dan log level non-debug.
- APP key dan HMAC key wajib berbeda, stabil, di-escrow, serta tidak dibuat ulang saat deploy.

## 5. Cache And Build Compatibility

Urutan berikut diuji tanpa error:

1. `php artisan optimize:clear`
2. `php artisan config:cache`
3. `php artisan route:cache`
4. `php artisan view:cache`
5. `php artisan about` dengan config/routes/views cached
6. `php artisan route:list` menampilkan 122 route
7. `php artisan optimize:clear`

Tidak ada closure/config blocker yang menghalangi deployment cache. Cache dibersihkan kembali setelah audit agar test environment tidak berisiko memakai cached development configuration.

## 6. Stateful Services

| Area | Kondisi aktual | Kebutuhan produksi |
|---|---|---|
| Session | Database, HTTP-only, SameSite Lax; secure cookie false di local | Secure cookie true di HTTPS |
| Cache | Database | Layak untuk skala awal; monitor contention/size |
| Queue | Database configured | Tidak ada job aplikasi aktif; worker belum wajib |
| Scheduler | Hanya command `inspire`; tidak ada task bisnis | Cron belum wajib |
| Mail | Log driver | SMTP/provider wajib untuk password reset |
| Maintenance | File driver | Gunakan saat deployment atomic |

## 7. Storage Audit

- Dokumen baru disimpan oleh `EmployeeDocumentStorageService` ke disk `private` (`storage/app/private`).
- Foto baru disimpan oleh `EmployeePhotoStorageService` ke disk `private`.
- Preview/download memakai streamed authorized response dan header private/no-store.
- Kedua service masih dapat membaca legacy disk `public` untuk kompatibilitas migrasi.
- Development audit: private storage 0 business files; public employee storage memiliki 6 file (3 kategori documents, 3 photos), total 6.25 MB.
- Command dry-run dokumen dan foto melaporkan 0 record database untuk dipindahkan. Artinya enam file tersebut tampak orphan/unreferenced, tetapi isinya tidak diperiksa atau dihapus pada Tahap 8.
- Sampai diklasifikasi, file tersebut harus diperlakukan sebagai data sensitif dan ikut backup; public exposure harus ditutup sebelum go-live.
- Import spreadsheet diproses dari upload sementara dan tidak menjadi persistent business storage.

## 8. Backup And Restore Validation

### Storage

Uji archive/restore memakai file sintetis di temp directory:

- Archive dibuat: yes.
- SHA-256 result length: 64.
- Files checked: 2.
- Checksum mismatch: 0.
- Restore: PASS.
- Temporary artifacts removed: yes.

Tidak ada file pegawai nyata yang disalin atau ditampilkan.

### Database

Host audit tidak menyediakan executable `mysqldump` dan `mysql`. Oleh karena itu database dump/restore penuh ke database sementara **belum diuji**. Database development tidak diubah. Full isolated restore drill diwajibkan sebelum go-live mengikuti runbook.

Rekomendasi provisional: backup harian + pre-deployment, retensi 7 daily/4 weekly/12 monthly, RPO 24 jam, RTO 4 jam. Angka ini menunggu persetujuan pemilik bisnis.

## 9. Dependency Audit

### Composer Production Lock

`composer audit --locked --no-dev` gagal dengan **30 advisory pada 10 package**. Advisory mencakup severity high/medium/low pada antara lain:

- `laravel/framework` (13.7.0)
- `guzzlehttp/guzzle`
- `guzzlehttp/psr7`
- `league/commonmark`
- komponen Symfony HTTP, routing, mailer, dan MIME

Karena terdapat advisory high pada framework/request/mail parsing path, kondisi ini adalah blocker production. Package tidak di-update pada Tahap 8 sesuai scope.

### npm Production Dependencies

`npm audit --omit=dev` menemukan 2 package berseverity high:

- direct `axios` 1.16.0 (beberapa advisory; fixed version tersedia)
- transitive `form-data` 4.0.5 (CRLF injection; fixed version tersedia)

Ini juga blocker production. `npm ls --depth=0` melaporkan beberapa optional native package sebagai extraneous pada node_modules development; clean `npm ci` wajib menjadi sumber build release.

## 10. Debug And Artifact Audit

- Tidak ditemukan `dd`, `dump`, `var_dump`, `print_r`, `console.log`, TODO/FIXME/HACK aktif di source aplikasi setelah mengecualikan vendor/node_modules/third-party public assets.
- Tidak ditemukan debug route (`telescope`, `horizon`, `ignition`, `phpinfo`) aktif.
- Tidak ditemukan dump/backup/log/env yang tracked selain `.env.example` dan `.gitignore` placeholder storage.
- `composer.lock`, `package-lock.json`, dan `.env.example` tracked.
- Build warning hanya timing plugin Vite; build tetap sukses dan tidak ada unresolved asset.

## 11. Deployment And Rollback Strategy

- Gunakan build bersih dari lockfiles dan artifact release immutable.
- Deploy atomic menggunakan release directory/symlink.
- Backup database dan private storage sebelum migration/deploy.
- Migration produksi hanya `php artisan migrate --force` setelah review dan approval; jangan jalankan seeder.
- Cache config/routes/views setelah environment final.
- Smoke test role, dokumen, QR/e-card, attendance, report, import/export, dan password reset sebelum membuka traffic.
- Rollback code memakai release sebelumnya. Jangan otomatis `migrate:rollback`; jika schema/data tidak kompatibel, restore snapshot database + file dengan approval incident commander.

Detail operasional:

- `docs/deployment/production-environment.md`
- `docs/deployment/production-deployment-checklist.md`
- `docs/deployment/backup-restore-runbook.md`

## 12. Findings

| ID | Severity | Area | Finding | Required action |
|---|---|---|---|---|
| DEP-001 | BLOCKER | Composer | 30 advisory produksi pada 10 package, termasuk high | Upgrade terkontrol, audit ulang, full regression |
| DEP-002 | BLOCKER | npm | 2 high production dependency findings (`axios`, `form-data`) | Update lockfile terkontrol, build/audit/test ulang |
| DEP-003 | BLOCKER | Backup | Full MySQL dump/isolated restore belum diuji karena client tool tidak tersedia | Jalankan restore drill di staging/backup host |
| DEP-004 | BLOCKER | Storage | 6 orphan legacy employee files berada di public storage | Klasifikasi, backup, lalu move/delete terotorisasi sebelum go-live |
| DEP-005 | HIGH | Mail | Development memakai mail log; provider produksi belum dipilih/diuji | Konfigurasi SMTP dan uji password reset |
| DEP-006 | HIGH | Infrastructure | Hosting, domain, TLS, monitoring, dan service account belum final | Lengkapi sebelum production deployment |
| DEP-007 | MEDIUM | Privacy/logging | Token invitation dapat muncul di infrastructure access log | Terapkan query/path redaction dan uji |
| DEP-008 | MEDIUM | Operations | RPO/RTO dan retention belum disetujui bisnis | Approval owner sebelum go-live |
| DEP-009 | MEDIUM | Session | Secure cookie false pada local baseline | Set true dan verifikasi di HTTPS production |
| DEP-010 | LOW | Composer metadata | Exact constraints QR/Spreadsheet menghasilkan warning validate | Review pada dependency maintenance, bukan Tahap 8 |
| DEP-011 | LOW | node_modules | Optional native packages dilaporkan extraneous | Gunakan clean `npm ci` di build host |
| DEP-012 | INFO | Queue/scheduler | Queue configured tetapi tidak ada queued job; scheduler kosong | Tidak perlu worker/cron saat ini; audit tiap release |

## 13. Entry Criteria For Stage 9

Sistem boleh masuk UAT internal dengan batasan berikut:

- UAT tidak diperlakukan sebagai production/go-live.
- Jangan memakai data pribadi produksi pada host UAT yang belum memenuhi kontrol produksi.
- Dependency blocker dicatat dan harus ditutup sebelum release candidate production.
- Public orphan files tidak boleh dipromosikan ke environment UAT/production.
- Temuan UAT tidak boleh mengubah baseline tanpa issue, test, dan regression yang tercatat.

## 14. Stage 8 Verification

Hasil final setelah dokumentasi:

- `php artisan test`: 297 passed, 2470 assertions, 0 failed, 0 skipped.
- `npm run build`: PASS (Vite 8.0.10, 56 modules transformed; CSS 28.47 kB dan JS 86.49 kB sebelum gzip).
- `php artisan migrate:status`: 26 Ran, 0 Pending.
- `git diff --check`: PASS.
- Browser smoke desktop 1440x900: root redirect ke `/login`, title benar, form login tersedia, 0 broken image, 0 horizontal overflow.
- Browser smoke mobile 390x844: form terlihat, 0 broken image, 0 horizontal overflow; bounds form 16-374.4 px berada di dalam viewport.
- Browser console: 0 warning/error pada alur guest root/login.

Tidak ada source code aplikasi, migration, seeder, dependency, atau database development yang diubah. Working tree akhir hanya berisi empat dokumen Tahap 8 yang belum di-commit.
