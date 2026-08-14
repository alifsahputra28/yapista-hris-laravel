# YAPISTA HRIS Security Hardening

## 1. Scope

- Tahap: Stage 3 - Security Hardening.
- Baseline fungsional: 253 tests, 2.038 assertions, 0 failed, 0 skipped.
- Audit mencakup 122 route aktif dan role `super_admin`, `hr_admin`, `panitia`, dan `pegawai`.
- Area: authentication, authorization, IDOR, CSRF, XSS, mass assignment, NIK, data sensitif, dokumen, foto, upload, QR, attendance, session, secret, logging, import/export, response header, dan dependency.
- Tidak ada migration/schema, seeder, key rotation, dependency install/update, atau perubahan business rule NUP.

## 2. Security Baseline

Kontrol yang sudah ada sebelum perubahan:

- Laravel Breeze melakukan session regeneration setelah login serta invalidate session dan regenerate CSRF token saat logout.
- `LoginRequest` memakai rate limit 5 percobaan berdasarkan email dan IP.
- Public `/register` tidak membuat akun; onboarding hanya melalui kode undangan.
- Route administratif memakai `auth` dan `role` middleware.
- Resource profil berulang memakai employee dari user login dan pemeriksaan ownership.
- Dokumen tersimpan pada disk `private`, disajikan oleh controller setelah Policy, dan path dinormalisasi.
- NIK memakai Laravel encryption dan exact HMAC lookup dengan key terpisah.
- QR memakai token acak 64 karakter, hash lookup, encrypted token storage, revocation, dan satu token aktif secara logic.
- Automatic attendance menerima payload QR, resolve token ke employee, lalu memakai `EventAttendanceService`.
- Import employee dibatasi role, ukuran 5 MB, MIME/extension, header tetap, dan 1.000 data row.
- XLSX export memakai `inlineStr`; nilai berawalan `=`, `+`, `-`, atau `@` tidak dieksekusi sebagai formula.

Perubahan Stage 3:

- Registrasi undangan dikunci ke email undangan, kode baru diperpanjang menjadi 32 karakter acak, dan route dibatasi 10 request/menit.
- Upload foto baru pindah dari public ke private storage.
- Foto hanya disajikan melalui endpoint berotorisasi dan respons `no-store`.
- Command migrasi foto legacy tersedia dengan default dry-run dan mode commit eksplisit.
- Field sensitif terenkripsi dan internal file/token path ditambahkan ke `$hidden`.
- Header `nosniff`, `SAMEORIGIN`, Referrer Policy, dan Permissions Policy ditambahkan secara global.
- `.env.example` mendokumentasikan cookie secure/HttpOnly/SameSite tanpa memaksa local HTTP memakai secure cookie.

## 3. Authentication

- Login: `AuthenticatedSessionController::store()` memakai `LoginRequest`, session regeneration, dan server-side role redirect.
- Logout: session di-invalidate dan CSRF token dibuat ulang.
- Remember me: tetap diteruskan ke `Auth::attempt()`.
- Password reset: route Breeze tetap memakai guest middleware dan password broker.
- Password update/delete account: current password validation tetap aktif pada flow terkait.
- Public registration: GET/POST `/register` hanya redirect ke login dan tidak membuat user.
- Login throttling: 5 percobaan, limiter dibersihkan setelah autentikasi berhasil.
- Invitation: single-use, expiring, role selalu `pegawai`, email sekarang harus sama persis setelah normalisasi lowercase.

## 4. Authorization

| Area | Super Admin | HR Admin | Panitia | Pegawai |
|---|---:|---:|---:|---:|
| Dashboard utama | Ya | Ya | Dashboard scanner | Beranda sendiri |
| Unit/Jabatan | CRUD | CRUD | Tidak | Tidak |
| Pegawai/Verification/Invitation | CRUD | CRUD | Tidak | Tidak |
| Import employee | Ya | Ya | Tidak | Tidak |
| Profile/family/education/certification/admin detail | Lihat melalui area HR | Lihat melalui area HR | Tidak | CRUD milik sendiri sesuai status |
| Dokumen | Sesuai Policy | Sesuai Policy | Tidak | Milik sendiri |
| QR generate/regenerate | Ya | Ya | Tidak | Tidak |
| ID Card | Preview employee | Preview employee | Tidak | Kartu sendiri |
| Event/participant management | Ya | Ya | Tidak | Tidak |
| Scanner/manual attendance | Ya | Ya | Ya | Tidak |
| Delete attendance | Ya | Ya | Tidak | Tidak |
| Reports/export | Ya | Ya | Tidak | Tidak |

Evidence utama: `routes/web.php`, `routes/auth.php`, `RoleMiddleware`, document Policy, dan full feature suite.

## 5. IDOR / Ownership

- Family, education, dan certification resolve resource lalu memanggil ownership check; cross-employee menghasilkan 404.
- Profile, wizard, administrative detail, activity, document upload, dan self ID Card mengambil employee dari user login.
- Document access memakai `Gate::authorize()` sebelum storage lookup/response.
- Foto baru memakai `EmployeePhotoController`: HR/Admin dapat melihat employee, pegawai hanya miliknya, panitia ditolak, guest diarahkan login.
- Route foto tidak menerima path dari request; path diambil dari row employee dan tetap dinormalisasi untuk menolak `..` serta null byte.
- Security test membuktikan owner/admin allowed, employee lain 404, panitia 403, guest redirect, dan traversal 404.

## 6. NIK & Sensitive Data

- Source aktif NIK: `nik_encrypted`; exact lookup: `nik_lookup` HMAC SHA-256.
- `EMPLOYEE_NIK_LOOKUP_KEY` harus string minimal 32 karakter; missing key gagal aman tanpa mencetak NIK/key.
- Model interface `$employee->nik` tetap melakukan decrypt hanya di memory.
- `nik`, ciphertext, lookup, migration marker, dan nomor KK disembunyikan dari serialisasi Employee.
- NIK keluarga, nomor ijazah, dan nomor sertifikat memakai encrypted cast serta sekarang hidden dari serialisasi.
- Rekening, pajak, dan BPJS sudah encrypted, hidden, dan memiliki masked accessor.
- File path dokumen dan kode undangan sekarang hidden dari serialisasi accidental.
- `php artisan employee-security:verify-nik`: 0 issue pada database development.
- Report/export/import tidak memuat NIK, KK, rekening, BPJS, pajak, certificate number, QR token, atau ciphertext.
- NUP tetap `employees.employee_number`, plaintext, 10 digit, unique, dapat ditampilkan, dan bukan secret/QR payload.

## 7. QR Security

- Payload: `YAPISTA:EMPLOYEE:{64-character random token}`.
- Database menyimpan SHA-256 token hash dan encrypted raw token; keduanya `$hidden`.
- Token lama revoked saat regenerate dan resolve hanya menerima active/non-revoked token.
- Generate/regenerate hanya super_admin/hr_admin; employee dan panitia tidak dapat memanggil endpoint.
- Raw token tidak tampil sebagai teks, data attribute, report, export, flash, atau explicit log.
- SVG yang dirender raw di Blade hanya berasal dari `QrCodeRenderer` dengan payload backend, bukan HTML input user.

## 8. Documents & Uploads

- Employee documents tetap pada disk private dan diakses via authorized controller.
- File path berasal dari database record yang sudah diotorisasi; normalisasi menolak absolute/traversal/null-byte path.
- Dokumen menerima PDF/JPG/JPEG/PNG, validasi MIME, extension, dan maksimal 5 MB.
- Foto sekarang menerima hanya JPG/JPEG/PNG/WebP, validasi MIME dan extension, maksimal 2 MB.
- Uploaded photo memakai generated storage filename, bukan original filename.
- Foto baru disimpan ke `storage/app/private/employee-photos` dan tidak melalui public symlink.
- Legacy public photo tetap dapat dibaca sementara melalui endpoint terotorisasi agar UI tidak rusak.
- Command `employee-security:migrate-photos-private` default dry-run; `--commit` memindahkan file dan menghapus legacy public copy setelah private copy terverifikasi.
- Development dry-run aktual: checked 0, ready 0, invalid 0, failed 0. Commit development tidak dijalankan.

## 9. Attendance / Scanner

- Scanner route hanya menerima `qr_payload`/legacy alias `payload`; `employee_id` biasa tidak menjadi identitas QR.
- QR token resolve ke employee, lalu `EventAttendanceService` memeriksa event, employee, participant, dan duplicate.
- Manual attendance menerima employee ID hanya pada role admin/HR/panitia dan tetap memakai service yang sama.
- Unique event + employee tetap menjadi defense database; duplicate constraint dipetakan ke already-attended dan exception lain dilempar ulang.
- NUP langsung, malformed QR, revoked QR, cancelled participant, dan event inactive ditolak oleh existing regression tests.

## 10. Import / Export

- Import hanya super_admin/hr_admin.
- Accepted: XLSX/XLS/CSV, MIME allowlist, 5 MB, fixed header, maksimum 1.000 data row.
- File dibaca dari temporary upload dan tidak dipindah ke public/permanent storage.
- Spreadsheet tidak dapat mengatur role, password, arbitrary verification status, QR token, NIK ciphertext/lookup, atau admin flag.
- Rule verified/draft dan QR ditentukan backend dari NUP sesuai business rule Stage 2.
- Malformed workbook menghasilkan pesan generik; row error tidak menampilkan data sensitif.
- Export hanya field operasional yang diperlukan. Custom writer memakai escaped XML `inlineStr`, sehingga spreadsheet formula tidak dieksekusi.

## 11. Session & Cookies

- Driver database; lifetime 120 menit; HttpOnly default true; SameSite `lax`; encryption dapat diatur environment.
- `.env.example` sekarang eksplisit menyediakan `SESSION_SECURE_COOKIE=false`, `SESSION_HTTP_ONLY=true`, dan `SESSION_SAME_SITE=lax` untuk local baseline.
- Production HTTPS wajib mengubah `SESSION_SECURE_COOKIE=true`.
- Secure cookie tidak di-hardcode agar local HTTP tetap berfungsi.

## 12. Secrets & Environment

- `APP_KEY` dan `EMPLOYEE_NIK_LOOKUP_KEY` dikonfigurasi pada development; nilainya tidak dicetak.
- `.env` di-ignore; `git ls-files` hanya menemukan `.env.example`, tanpa key/PEM/SQL dump/backup archive tracked.
- DB credential berasal dari environment/config, bukan literal repository.
- `composer.json` memiliki script `setup` yang menjalankan `key:generate`; script ini hanya boleh untuk instalasi baru dan dilarang pada deployment dengan encrypted production data.
- Production wajib: `APP_ENV=production`, `APP_DEBUG=false`, HTTPS, non-root DB user least privilege, private storage permission, dan secret persistence.
- Tidak ada key rotation pada Stage 3.

## 13. Dependency Audit

### Composer

`composer audit --format=json` selesai dengan exit non-zero karena **30 advisories pada 10 package**: 8 high, 20 medium, 1 low, dan 1 advisory tanpa severity.

| Package | Installed | Direct | Highest | Minimum safe line dari audit |
|---|---:|---:|---:|---|
| laravel/framework | 13.7.0 | Ya | High | 13.12.0 untuk seluruh advisory terdeteksi |
| guzzlehttp/guzzle | 7.10.0 | Tidak | High | 7.15.2 |
| guzzlehttp/psr7 | 2.9.0 | Tidak | Medium | 2.12.3 |
| league/commonmark | 2.8.2 | Tidak | High | 2.9.0 |
| symfony/http-foundation | 7.4.8 | Tidak | Medium | 7.4.13 |
| symfony/http-kernel | 7.4.8 | Tidak | High | 7.4.12 |
| symfony/mailer | 7.4.8 | Tidak | Medium | 7.4.12 |
| symfony/mime | 7.4.9 | Tidak | High | 7.4.12 |
| symfony/routing | 7.4.9 | Tidak | Medium | 7.4.13 |
| symfony/polyfill-intl-idn | current lock | Tidak | Low | 1.38.1 |

No Composer package was changed. Controlled dependency remediation is required before release; this Stage explicitly prohibited `composer update`.

### npm

`npm audit --json` reports **7 vulnerable packages: 2 critical, 5 high**.

| Package | Installed | Direct | Severity | Affected range |
|---|---:|---:|---:|---|
| axios | 1.16.0 | Ya | High | 1.0.0 - 1.17.0 |
| concurrently | 9.2.1 | Ya | Critical | 9.2.1, via shell-quote |
| postcss | 8.5.14 | Ya | High | <=8.5.22 |
| vite | 8.0.10 | Ya | High | 8.0.0 - 8.0.15 |
| form-data | 4.0.5 | Tidak | High | 4.0.0 - 4.0.5 |
| nanoid | 3.3.12 | Tidak | High | <=3.3.16 |
| shell-quote | 1.8.3 | Tidak | Critical | <=1.8.4 |

No npm package or lockfile was changed. Most npm findings affect development/build tooling, but Axios is shipped client-side; controlled lockfile remediation and regression build are release prerequisites.

## 14. Production Hardening Requirements

- Set `APP_ENV=production` and `APP_DEBUG=false`.
- Enable HTTPS and `SESSION_SECURE_COOKIE=true`.
- Preserve APP_KEY and NIK lookup key; never run `composer run setup`/`key:generate` on existing encrypted data.
- Use a non-root database user with least privilege.
- Run photo migration dry-run, back up storage, then run `employee-security:migrate-photos-private --commit` before exposing production.
- Confirm no public legacy photo copies remain after migration.
- Keep employee documents and photos outside the public symlink with correct filesystem permissions.
- Redact invitation URL paths in reverse-proxy/access logs; the application itself does not log tokens.
- Upgrade vulnerable Composer/npm packages in a controlled release branch and rerun the full suite/build.
- Stage CSP after inventory/removal of Mantis inline scripts and external Google Fonts; a guessed strict CSP was intentionally not enabled.
- Review Google Fonts privacy/network dependency during UI/performance stages.
- Do not deploy test routes, local credentials, dumps, `.env`, or private storage artifacts.

## 15. Security Findings

| ID | Severity | Area | Finding | Evidence | Fix | Test | Status |
|---|---|---|---|---|---|---|---|
| SEC-001 | HIGH | Invitation | Valid code accepted an arbitrary account email and new token entropy was only six base36 characters. | `InvitationRegisterController`, `EmployeeInvitationController`; old invitation test used a different email. | Exact invited-email binding, valid-email prerequisite, 32 random chars, 10/min throttle. | `SecurityHardeningTest`, `EmployeeInvitationTest`. | FIXED |
| SEC-002 | MEDIUM | Employee photo | Upload and direct Blade URL used public storage. | Three upload controllers and seven Blade references used public disk/`asset('storage/...')`. | Private storage service, authorized controller, protected URL, strict image validation, migration command. | Owner/admin/cross-user/panitia/guest/traversal/migration tests. | FIXED; production migration required |
| SEC-003 | MEDIUM | Serialization | KK, family NIK, certificate numbers, document path, and invitation code could appear in accidental model serialization. | Missing `$hidden` on affected models. | Added explicit `$hidden`; encrypted casts retained. | Serialization assertions in `SecurityHardeningTest`. | FIXED |
| SEC-004 | MEDIUM | HTTP headers | General web responses lacked a consistent minimum header baseline. | `bootstrap/app.php` had no security-header middleware. | Added scoped middleware for nosniff, SAMEORIGIN, referrer, and permissions policy. | Header assertions on login response. | FIXED |
| SEC-005 | HIGH | Composer dependency | 30 advisories, including framework/mail/URL/HTTP parsing and DoS issues. | Actual `composer audit`; versions listed above. | No update allowed in Stage 3; controlled compatible update required. | Audit command only. | DEFERRED - release blocker |
| SEC-006 | HIGH | npm dependency | 2 critical and 5 high findings in direct/transitive build/client packages. | Actual `npm audit`; versions listed above. | No update allowed in Stage 3; controlled lockfile update required. | Audit command and successful current build. | DEFERRED - release blocker |
| SEC-007 | MEDIUM | CSP | A strict CSP is not active; Mantis uses inline scripts and Google Fonts. | Auth/admin Blade layouts and asset inventory. | Inventory nonces/self-host fonts before staged CSP. | Manual code audit. | DEFERRED - Stage 5/8 |
| SEC-008 | LOW | Privacy | Google Fonts causes an external browser request. | Auth/admin layouts reference fonts.googleapis.com. | Evaluate self-hosting during UI/performance work. | Code audit. | DEFERRED - Stage 5/6 |
| SEC-009 | MEDIUM | Invitation logs | Invitation token is necessarily present in the registration URL and may enter infrastructure access logs. | Route `invitation/register/{code}`. No application token logging found. | Redact route in proxy/web access logs; keep app logs secret-free. | Logging/code search. | DEFERRED - Stage 8 |
| SEC-010 | MEDIUM | Production config | Local debug/cookie values are unsuitable for production and `setup` can generate a new APP_KEY. | `.env.example`, `config/session.php`, `composer.json`. | Explicit production checklist; preserve keys and do not run setup on existing data. | Configuration audit. | DEFERRED - Stage 8 |

Finding totals: **0 critical, 3 high, 6 medium, 1 low**. All application-level high findings are fixed. Two dependency high findings remain deferred under an explicit package-update prohibition and block release until remediated.

## 16. Verification Results

| Check | Actual result |
|---|---|
| SecurityHardening targeted | 8 passed, 55 assertions |
| Invitation | 5 passed, 31 assertions |
| Wizard profile | 11 passed, 95 assertions |
| Document security | 13 passed, 71 assertions |
| NIK security | 10 passed, 90 assertions |
| QR | 13 passed, 92 assertions |
| Event attendance | 14 passed, 99 assertions |
| Full `php artisan test` | **261 passed, 2.095 assertions, 0 failed, 0 skipped; 33.620s** |
| `npm run build` | PASS; Vite 8.0.10, 56 modules, 22.15s |
| Build output | CSS 50.06 kB (gzip 9.06), JS 86.49 kB (gzip 31.55) |
| `git diff --check` | PASS at pre-document verification; final check required after this document |
| Migration | 25 Ran, 0 Pending; no migration executed |
| Seeder | Not executed |
| Photo development migration | Dry-run only; 0 checked/ready/failed; commit not executed |
| Package changes | None |

## 17. Stage 3 Exit Decision

- Runtime application controls are sufficiently hardened to continue to Stage 4 Database & Data Integrity.
- Stage 4 may proceed because there are no open critical findings and no unresolved application-level high finding.
- Production release is **not** approved yet: Composer/npm advisories, production environment values, infrastructure access-log redaction, HTTPS/cookie settings, and final photo migration must be closed during controlled release/deployment preparation.
- Stage 4 is not performed in this document.
