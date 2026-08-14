# YAPISTA HRIS UAT & Deployment Blocker Resolution

## 1. Baseline

Audit dilakukan pada 14 Agustus 2026 (Asia/Jakarta).

| Item | Baseline masuk Tahap 9.5 | Hasil akhir |
|---|---|---|
| Branch / HEAD | `main` / `4ba68a0` | Tidak berubah; perubahan Tahap 9.5 belum di-commit |
| Migration | 26 Ran, 0 Pending | 26 Ran, 0 Pending |
| Test | 297 passed, 2470 assertions | 297 passed, 2470 assertions |
| Frontend | Vite 8.0.10, PASS | Vite 8.0.16, PASS |
| Git diff check | PASS | PASS |

Lima dokumen UAT yang sudah ada tetap dipertahankan dan tidak ditimpa:

- `docs/finalization/09-uat-pilot-internal.md`
- `docs/uat/uat-test-scenarios.md`
- `docs/uat/uat-issue-register.md`
- `docs/uat/post-v1-backlog.md`
- `docs/uat/uat-signoff.md`

Human UAT belum dijalankan dan tidak dinyatakan lulus dalam laporan ini.

## 2. Composer Advisory

### Kondisi awal

`composer audit` menemukan **30 advisory pada 10 package**: 8 high, 20 medium, 1 low, dan 1 tanpa severity. Seluruhnya berada pada dependency production; tidak ada yang diterima sebagai risiko dev-only.

| Package | Awal | Direct | Risiko/reachability | Minimum safe line | Akhir |
|---|---:|---:|---|---:|---:|
| laravel/framework | 13.7.0 | Ya | Runtime framework, HTTP, routing | 13.12.0 | 13.25.0 |
| guzzlehttp/guzzle | 7.10.0 | Tidak | Shipped runtime HTTP dependency | 7.15.2 | 7.15.3 |
| guzzlehttp/psr7 | 2.9.0 | Tidak | Runtime HTTP message parsing | 2.12.3 | 2.13.0 |
| league/commonmark | 2.8.2 | Tidak | Runtime framework dependency | 2.9.0 | 2.10.0 |
| symfony/http-foundation | 7.4.8 | Tidak | Runtime request/response | 7.4.13 | 7.4.16 |
| symfony/http-kernel | 7.4.8 | Tidak | Runtime HTTP kernel | 7.4.12 | 7.4.16 |
| symfony/mailer | 7.4.8 | Tidak | Reachable melalui password reset | 7.4.12 | 7.4.15 |
| symfony/mime | 7.4.9 | Tidak | Reachable melalui mail | 7.4.12 | 7.4.16 |
| symfony/polyfill-intl-idn | 1.37.0 | Tidak | Runtime polyfill | 1.38.1 | 1.38.1 |
| symfony/routing | 7.4.9 | Tidak | Runtime routing/signed URL | 7.4.13 | 7.4.15 |

Advisory IDs awal:

- Laravel: `PKSA-m5cs-t1y6-qpcs`, `PKSA-3r5d-mb8f-1qw9`, `PKSA-mdq4-51ck-6kdq` (`CVE-2026-48019` pada advisory ketiga).
- Guzzle: `PKSA-gcrk-3vtt-1r14`, `PKSA-cnw1-2ytm-cgr8`, `PKSA-fy2t-3c5f-827y`, `PKSA-qxvb-2bpp-dnk6`, `PKSA-bbs6-q5q9-f3t4`, `PKSA-bcdd-5xc7-gwfb`, `PKSA-pwsk-hy21-4gby`, `PKSA-93qv-9n9h-6k6p`, dan `PKSA-k22t-f949-t9g6`.
- PSR-7: `PKSA-vznr-tgp9-fd7d`, `PKSA-7qs6-zvnz-h66r`, `PKSA-gm5x-j3mz-71n9`, `PKSA-jj5t-2zs1-dcfm`.
- CommonMark: `PKSA-5mzr-szzf-z6cn`, `PKSA-cqd6-fg4n-nxpf`, `PKSA-1q6p-sqkj-8mmj`, `PKSA-mc58-w91n-f5gv`, `PKSA-t21r-vtr5-3mdz`, `PKSA-scnn-p8mm-jbft`.
- Symfony: `PKSA-y6py-qpv1-h52p`, `PKSA-dw7n-x7f5-zf63`, `PKSA-28rh-rzzn-djk4`, `PKSA-wtxr-p26d-nn42`, `PKSA-2n2k-66v2-bwg3`, `PKSA-dwsq-ppd2-mb1x`, `PKSA-bf7t-jnpz-492k`, dan `PKSA-yc7t-91v9-99xs`.

### Remediasi

Perintah scoped yang digunakan adalah `composer update laravel/framework --with-all-dependencies`. Tidak ada global `composer update` dan tidak ada major upgrade. Karena Laravel adalah direct dependency yang mengikat komponen framework, lockfile memperbarui 40 package kompatibel dalam Laravel 13/Symfony 7.4. Package security-relevant tercantum pada tabel di atas; package pendukung ikut bergerak sesuai dependency solver.

Composer global 2.8.5 pada workstation tidak dapat di-self-update karena permission. Verifikasi/install kemudian dijalankan dengan Composer resmi 2.10.2 sementara setelah SHA-256 distribusinya diverifikasi. File sementara sudah dihapus setelah audit. CI/production wajib menggunakan Composer 2.10.2 atau lebih baru.

Hasil akhir:

- `composer audit --locked --no-dev`: **No security vulnerability advisories found**.
- `composer validate --no-check-publish`: valid; hanya warning constraint exact untuk QR dan spreadsheet yang sudah ada.
- `composer check-platform-reqs --no-dev`: PASS.
- `composer.json` tidak berubah; `composer.lock` berubah.

## 3. npm Advisory

### Kondisi awal

`npm audit` menemukan 7 vulnerable packages: 2 critical dan 5 high.

| Package | Awal | Direct | Klasifikasi | Akhir |
|---|---:|---:|---|---:|
| axios | 1.16.0 | Ya | Browser runtime | 1.18.0 |
| form-data | 4.0.5 | Tidak | Transitive Axios/Node path; production lock | 4.0.6 |
| concurrently | 9.2.1 | Ya | Build/dev only | 9.2.4 |
| shell-quote | 1.8.3 | Tidak | Build/dev only | 1.9.0 |
| postcss | 8.5.14 | Ya | Build/dev only | 8.5.23 |
| nanoid | 3.3.12 | Tidak | Build/dev only | 3.3.18 |
| vite | 8.0.10 | Ya | Build/dev only | 8.0.16 |

Remediasi dilakukan dengan install versi target, bukan `npm update` atau `npm audit fix --force`. `package.json` menambahkan override `form-data: 4.0.6` agar lockfile tidak kembali memilih 4.0.5. `npm ci` kemudian membangun dependency tree dari lockfile.

Hasil akhir:

- `npm audit`: **0 vulnerabilities**.
- `npm audit --omit=dev`: **0 vulnerabilities**.
- `npm run build`: PASS, Vite 8.0.16, 57 modules transformed.
- `package.json` dan `package-lock.json` berubah.
- Beberapa optional native package tetap ditandai extraneous oleh `npm ls` pada Windows; tidak memiliki advisory dan build bersih tetap lulus. Ini catatan build-host, bukan runtime blocker.

## 4. Database Backup Drill

Driver aktual adalah MySQL 8.4.3. Credential diambil dari konfigurasi proses dan tidak dicetak ke command/report.

| Evidence | Hasil |
|---|---|
| Method | `mysqldump --single-transaction` dengan trigger/event/routine dan `utf8mb4` |
| Temporary file | `yapista-hris-restore-drill-20260814-194312.sql` di system temp |
| Size | 61,165 bytes |
| SHA-256 | `49687b5ee8d304c463af3c24fabb8f437c268b0b2487f2234bc2196a5463cd9c` |
| Timestamp | 2026-08-14 19:43:13 +07:00 |
| Backup readable | PASS |
| Temporary SQL after drill | Removed |

Password tidak ditempatkan pada command line atau nama file. Backup tidak masuk repository.

## 5. Database Restore Drill

Backup dipulihkan ke database disposable `yapista_hris_restore_test_20260814194312`, bukan database development/testing aktif. Restore berhasil dan database sementara dihapus setelah seluruh verifikasi selesai.

## 6. Restore Verification

| Table | Source | Restored | Match |
|---|---:|---:|---:|
| users | 17 | 17 | Yes |
| employees | 14 | 14 | Yes |
| institutions | 7 | 7 | Yes |
| positions | 37 | 37 | Yes |
| employee_documents | 0 | 0 | Yes |
| employee_qr_tokens | 13 | 13 | Yes |
| events | 5 | 5 | Yes |
| event_participants | 34 | 34 | Yes |
| event_attendances | 9 | 9 | Yes |

Verifikasi tambahan:

- Restored migration status: 26 Ran, 0 Pending.
- NIK encrypted decrypt: PASS; plaintext tidak dicetak.
- NIK HMAC blind-index exact match: PASS; lookup tidak dicetak.
- QR active/revoked restored: 13 / 0.
- Satu QR aktif dapat di-resolve dengan service existing: PASS; payload/token tidak dicetak.
- Metadata dokumen cocok: PASS (0/0).
- Physical private employee files tetap merupakan backup stream terpisah dari database dan wajib dibackup bersama release restore point.

## 7. Public Employee File Audit

Sebelum remediasi:

| Lokasi | Employee-related files | Size |
|---|---:|---:|
| `public/` langsung | 0 | 0 |
| `storage/app/public` | 6 | 6,549,811 bytes |
| `storage/app/private` | 0 business files | 0 |

Enam file tersebut berada di bawah jalur employee pada public disk dan dapat dicapai melalui junction `public/storage`. Seluruhnya PNG. Database memiliki 0 referensi foto dan 0 referensi dokumen; pencarian tracked source juga menemukan 0 referensi static/fallback. Klasifikasi akhir: REFERENCED 0, STATIC ASSET 0, ORPHAN CANDIDATE 6, UNKNOWN 0, CONFIRMED ORPHAN 6.

## 8. Orphan File Remediation

Keenam confirmed orphan dipindahkan ke quarantine private:

`storage/app/private/quarantine/employee-orphans/20260814-194430/`

Manifest internal menyimpan original/new relative path, ukuran, alasan, dan timestamp tanpa isi file. Hasil:

- Quarantined: 6.
- Permanently deleted: 0.
- Public employee files remaining: 0.
- Public exposure melalui `public/storage`: CLOSED untuk keenam file.
- Quarantine tidak masuk Git karena berada di private storage yang di-ignore.
- Dry-run migrasi foto: 0 record; dry-run dokumen: 0 record.

Penghapusan permanen hanya boleh dilakukan setelah retention/approval terpisah.

## 9. SMTP Readiness

- Password reset menggunakan Laravel notification/mail flow; targeted auth tests lulus.
- Invitation saat ini menghasilkan signed registration link tetapi tidak mengirim mail otomatis.
- Development mailer adalah `log`; ini hanya membuktikan application flow, bukan external delivery.
- `.env.example` dan `docs/deployment/production-environment.md` memuat variabel SMTP yang diperlukan tanpa credential.
- SMTP provider, credential, sender-domain verification, dan external delivery test: **INFRA ACTION REQUIRED / PENDING**.

## 10. Hosting / TLS Readiness

Dokumentasi produksi mewajibkan document root ke `public/`, `APP_URL=https://...`, sertifikat TLS valid, redirect HTTPS, dan `SESSION_SECURE_COOKIE=true`. Source tidak di-deploy pada tahap ini.

Hosting, domain/DNS, service account, reverse-proxy configuration, dan TLS certificate belum tersedia sebagai evidence: **INFRA ACTION REQUIRED / PENDING**.

## 11. Monitoring Readiness

Minimum operational checklist sudah mencakup:

- akses dan retensi Laravel log;
- HTTP login/health availability dan HTTP 5xx;
- database connectivity/latency;
- disk usage database, private storage, dan log;
- auth/mail failure;
- queue failure bila worker kelak diaktifkan.

Platform monitoring dan alert destination belum dipilih karena server belum tersedia: **INFRA ACTION REQUIRED / PENDING**.

## 12. Regression

| Check | Hasil |
|---|---|
| Targeted auth/document/photo/NIK/QR/e-card/import | 94 passed, 605 assertions |
| Full `php artisan test` | 297 passed, 2470 assertions, 0 failed, 0 skipped |
| `npm run build` | PASS, Vite 8.0.16 |
| `composer audit --locked --no-dev` | 0 advisory |
| `npm audit` / `npm audit --omit=dev` | 0 / 0 vulnerability |
| Migration | 26 Ran, 0 Pending |
| `git diff --check` | PASS |

Browser smoke aktual:

- `/` menuju `/login`; title dan form benar, logo lokal loaded, CSRF tersedia.
- `/dashboard`, `/employees`, employee detail, employee mobile home, e-card, documents, scanner, dan reports mengalihkan guest ke login.
- 0 broken image, 0 horizontal overflow, 0 browser console warning/error pada alur guest.
- Authenticated browser interaction belum dijalankan dengan credential manusia; authenticated rendering/authorization dibuktikan oleh automated regression dan tetap menjadi bagian human UAT.

## 13. Remaining Blockers

| ID | Type | Finding | Initial Status | Action | Evidence | Final Status |
|---|---|---|---|---|---|---|
| BLK-001 | DEPENDENCY | 30 Composer advisories | OPEN | Targeted Laravel 13 compatible update | Composer audit 0; full tests PASS | FIXED |
| BLK-002 | DEPENDENCY | 7 npm vulnerable packages | OPEN | Targeted package updates + transitive override | npm audit 0; build PASS | FIXED |
| BLK-003 | DATA RECOVERY | Backup/restore unproven | OPEN | Isolated MySQL dump/restore drill | Checksum, aggregate, migration, NIK, QR PASS | FIXED |
| BLK-004 | STORAGE | 6 public orphan employee files | OPEN | Confirm references then quarantine private | Public remaining 0; permanent delete 0 | FIXED |
| INF-001 | INFRASTRUCTURE | External SMTP unproven | OPEN | Configure provider and test delivery | Application flow only | PENDING |
| INF-002 | INFRASTRUCTURE | Hosting/domain/TLS unavailable | OPEN | Provision and validate HTTPS environment | Requirements documented | PENDING |
| INF-003 | INFRASTRUCTURE | Monitoring platform unavailable | OPEN | Configure minimum checks/alerts | Checklist documented | PENDING |
| UAT-001 | HUMAN UAT | Real pilot and sign-off not executed | OPEN | Execute UAT package with real roles/devices | UAT docs preserved | PENDING |

No dependency blocker was classified as ACCEPTED RISK; all detected advisories were remediated. Optional npm tree warnings are INFO only and do not hide an advisory.

## 14. Human UAT Prerequisites

Before sign-off, humans must provide/verify:

- pilot users for all roles;
- staging/hosting target and HTTPS URL;
- SMTP credentials and real password-reset delivery;
- physical QR/2D scanner;
- domain/DNS and valid TLS certificate;
- review of quarantined-file retention before permanent deletion;
- completed issue register and signed `docs/uat/uat-signoff.md`.

## 15. Release Gate

**READY FOR HUMAN UAT WITH INFRASTRUCTURE ACTIONS**

All four technical blockers entering Stage 9.5 are closed. SMTP, hosting/TLS, monitoring, physical scanner, real human UAT, and human sign-off remain pending. This is not approval for Stage 10 or production deployment.
