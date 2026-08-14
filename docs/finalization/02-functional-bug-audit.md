# YAPISTA HRIS Functional Bug Audit

## 1. Audit Metadata

- Waktu audit: 2026-08-12, Asia/Jakarta.
- Scope: Stage 2 - Functional Bug Audit.
- Baseline source: `docs/finalization/01-feature-freeze-baseline.md`.
- Branch: `main`.
- HEAD saat audit dimulai: `3120bfbc0e90de9dc8006d856039c2ca8319c4bd` (`Add feature tests for dashboard insights, employee e-card, import functionality, and self-service UI`).
- Kondisi berubah dari Stage 1: seluruh perubahan Stage 1 sudah dikomit oleh pengguna; working tree kembali clean sebelum Stage 2.
- Larangan dipatuhi: tidak ada feature baru, migration, seed development, package install/update, atau command destructive.

## 2. Baseline Sebelum Perbaikan

| Pemeriksaan | Hasil aktual |
|---|---|
| Git | `main`, HEAD `3120bfb`, clean, ahead `origin/main` 1 commit |
| Migration | 25 Ran, 0 Pending |
| Full test | 247 passed, 1,995 assertions, 0 failed, 0 skipped, 26.806s |
| Runtime/database | Laravel 13.7.0, PHP 8.3.16, MySQL development |
| Frontend | Vite 8.0.10; build baseline Stage 1 berhasil |

Audit mencakup 46 kelompok flow functional pada instruksi: auth/role, dashboard, master data, employee list/create/edit/detail, NUP, verification, account linking, employee mobile, seluruh profile submodule, documents, ID Card/QR, event/participant/attendance, import/export/template/report, seeder test, empty/null state, double submit, refresh/back, 403/404, dan error handling.

## 3. Bug Register

| ID | Severity | Area | Reproduksi dan root cause | Status | Fix / defer |
|---|---|---|---|---|---|
| FB-001 | HIGH | Employee create | Admin membuat employee dengan NUP valid 10 digit; record tetap `draft` dan tidak memiliki QR. `EmployeeController::store()` memaksa semua record menjadi draft. | FIXED | Status menjadi verified, metadata verifier disimpan, QR dibuat lewat `EmployeeQrTokenService` dalam transaction. |
| FB-002 | HIGH | Employee update | NUP valid ditambahkan ke employee draft; status tetap draft dan QR tidak dibuat. `EmployeeController::update()` tidak menerapkan rule employee existing. | FIXED | Transisi draft ke verified dan ensure QR dilakukan saat NUP valid ditambahkan. NUP verified tidak dapat dihapus oleh validasi existing. |
| FB-003 | HIGH | Employee deactivate / QR | Endpoint nonaktifkan hanya mengubah `employment_status`; token QR lama tetap aktif sebagai record. | FIXED | Nonaktifkan employee dan revoke token aktif dalam transaction menggunakan service QR existing. |
| FB-004 | MEDIUM | Employee master relation | Admin dapat mengirim `institution_id` dan `position_id` dari unit berbeda. Validasi hanya memakai dua `exists` independen. | FIXED | `position_id` sekarang harus exist di institution yang dipilih. |
| FB-005 | MEDIUM | Master data delete | Menghapus position/institution yang dipakai employee menghasilkan foreign-key `QueryException` dan HTTP 500. | FIXED | Dependency diperiksa sebelum delete; race/final constraint tetap ditangkap dan dikembalikan sebagai pesan bisnis. |
| FB-006 | MEDIUM | Master data duplicate | Nama unit duplicate dan nama jabatan duplicate dalam unit yang sama dapat dibuat. | FIXED | Validation unique unit dan unique jabatan per institution ditambahkan, dengan ignore current record saat update. |
| FB-007 | MEDIUM | Operational timestamps | `config/app.php` hardcoded UTC walau seluruh operasi dan label aplikasi memakai WIB/Asia Jakarta. | FIXED | Timezone menjadi `APP_TIMEZONE`, default `Asia/Jakarta`; `.env.example` mendokumentasikan setting. |
| FB-008 | MEDIUM | ID Card download | Tombol dan route Download tersedia, tetapi controller selalu redirect dengan pesan PDF belum tersedia. Ini adalah unfinished implementation yang sudah eksplisit di baseline. | OPEN | Tidak dibangun pada Stage 2 karena akan menjadi feature development. Putuskan implementasi atau hapus/relabel action pada tahap terpisah/Tahap 5. |

Ringkasan severity:

- BLOCKER: 0 ditemukan, 0 diperbaiki.
- CRITICAL: 0 ditemukan, 0 diperbaiki.
- HIGH: 3 ditemukan, 3 diperbaiki.
- MEDIUM: 5 ditemukan, 4 diperbaiki, 1 didefer dengan alasan scope.
- LOW functional: 0.

## 4. Reproduction Tests

File baru: `tests/Feature/FunctionalBugRegressionTest.php`.

Sebelum fix:

- 6 test dijalankan.
- 6 failed, 10 assertions.
- Kegagalan membuktikan status masih draft, QR tidak dibuat, relasi unit-jabatan diterima, duplicate master diterima, delete menghasilkan 500, dan timezone masih UTC.

Sesudah fix:

- 6 passed, 37 assertions, 0 failed.
- `EmployeeManagementTest` diperluas untuk memeriksa verified metadata, QR aktif, dan revoke saat employee dinonaktifkan.
- `EmployeeNumberConsistencyTest` disesuaikan dengan keputusan resmi: penambahan NUP memverifikasi employee dan NUP tersebut tidak dapat dilepas kembali.

## 5. Functional Flow Results

| Area | Flow yang diverifikasi | Hasil |
|---|---|---|
| Authentication/root | Guest `/` dan login, valid/invalid login, inactive account, logout, remember me, forgot password, authenticated `/login`, role redirect | PASS melalui Auth/LoginEntryPoint tests; browser admin, employee, dan panitia berhasil |
| Role authorization | super_admin, hr_admin, pegawai, panitia, guest terhadap area utama | PASS; tidak ada redirect lintas-role yang salah |
| Dashboard | KPI/chart dataset, empty data, action route | PASS melalui Dashboard tests dan browser admin |
| Institution/position | list/create/edit/update, duplicate, relation, delete in-use | PASS setelah FB-004 sampai FB-006 |
| Employee list | render, pagination/query persistence, search nama/email/HP/NUP, filter, exact secure NIK | PASS melalui Employee/Filter/NIK tests |
| Employee create/update | NUP existing, employee baru tanpa NUP, duplicate, valid relation, metadata verification, QR | PASS setelah FB-001/FB-002 |
| Employee detail | null relations, foto/dokumen kosong, verified/draft, e-card action | PASS via tests dan browser detail/edit |
| Verification/invitation | draft/submitted/rejected/approve, required data/doc, NUP, QR, double approval, invitation registration | PASS |
| Profile | show/edit/wizard/draft/submit, contact/address, null state, verified profile optional | PASS |
| Family | CRUD, ownership, empty state, locking | PASS |
| Education/certification | CRUD, highest education, expiry/date validation, ownership | PASS |
| Administration | create/update bank/tax/BPJS partial data, masking, leading zero | PASS |
| NIK | normalize/encrypt/decrypt/HMAC, duplicate, exact search, masking, null | PASS; architecture tidak diubah |
| Documents | private upload/replace/preview/download/delete, invalid/missing file, ownership/HR policy, related documents | PASS |
| QR lifecycle | generate, one active, regenerate, old revoked, payload resolve | PASS; NUP tidak dijadikan payload |
| ID Card/E-Card | eligible/unavailable state, data dinamis, fallback foto, QR rendering | PASS untuk browser card; PDF download tetap FB-008 |
| Events | CRUD/lifecycle, active/closed/cancelled, empty participant | PASS |
| Participants | generate/manual, duplicate, cancel/remove, inactive/invalid employee | PASS |
| Scanner/attendance | QR valid/malformed/revoked, NUP direct rejected, participant/cancelled/inactive event, duplicate/race, manual, history barcode | PASS |
| Import employee | modal endpoint, template, XLSX/CSV/XLS, invalid header/file/row, duplicate, business rule NUP/QR, summary | PASS |
| Export/report | employee/event/attendance filters, empty state, XLSX validity, historical labels, formula-safe string output | PASS |
| Seeder test | idempotency, employee existing/new, QR preservation | PASS; seeder development tidak dijalankan |
| Error/null/403/404 | missing physical document, ownership, unauthorized routes, empty relations/data, business errors | PASS |

## 6. Browser Smoke QA

Local server dipakai hanya untuk read-only navigation terhadap data development existing.

| Role/viewport | Halaman | Hasil |
|---|---|---|
| Super Admin/default desktop | Login, Dashboard, Data Pegawai, Detail, Edit, ID Card, Verification, Events, Employee Report | Semua render; KPI/chart dan e-card tampil; tidak ada Server Error |
| Pegawai existing/390px | Beranda, Kegiatan, ID Card, Dokumen, Akun | Semua render; bottom navigation tampil, sidebar tersembunyi, horizontal overflow false |
| Pegawai baru/390px | Beranda, unavailable ID Card, onboarding identification | Semua render; ID Card aman tanpa NUP/QR; wizard terbuka di langkah identitas |
| Panitia/390px | Dashboard, QR scanner, daftar hadir | Semua render; scanner input dan manual participant form tersedia; overflow false |

- Browser console: 0 error, 0 warning pada flow yang diperiksa.
- Scanner menampilkan input teks QR/2D, bukan kamera browser.
- Tidak ada form mutasi yang disubmit saat smoke QA.

## 7. Deferred Findings

### Stage 3 - Security Hardening

| ID | Severity | Finding | Evidence |
|---|---|---|---|
| SEC-001 | MEDIUM | Foto profil masih disimpan pada disk public, sementara dokumen pegawai sudah private. Perubahan storage membutuhkan audit URL, fallback, migration file, dan policy yang lebih luas. | `EmployeeController`, `EmployeeProfileController`, dan `EmployeeProfileWizardController` memakai `store(..., 'public')`. |

Kontrol existing yang tetap berfungsi: CSRF, auth/role middleware, document policy/private storage, NIK encryption + HMAC, masking, QR tokenization/revocation, login throttling, dan file validation.

### Stage 4 - Database & Data Integrity

Audit agregat read-only development:

- Employee dengan NUP valid tetapi belum verified: **1**.
- Employee verified tanpa NUP valid: **0**.
- Token QR aktif pada employee ineligible: **0**.

`DB-001` (HIGH): satu row legacy tidak konsisten dengan rule terbaru. Kode baru mencegah record baru, tetapi data existing tidak diubah pada Stage 2.

`DB-002` (MEDIUM): duplicate unit dan jabatan sekarang dicegah pada application validation, tetapi schema belum memiliki unique constraint name/global dan name+institution. Evaluasi constraint serta deduplication harus dilakukan pada Stage 4.

### Stage 5 - UI/UX Audit

- `UI-001`: action Download ID Card terlihat aktif tetapi backend menyatakan PDF belum tersedia (terkait FB-008).
- `UI-002`: sejumlah legacy layout/inline style masih ada menurut baseline Stage 1; tidak menyebabkan flow gagal dan tidak disentuh pada Stage 2.

## 8. Files Changed

File baru:

- `tests/Feature/FunctionalBugRegressionTest.php`
- `docs/finalization/02-functional-bug-audit.md`

File diubah:

- `.env.example`
- `config/app.php`
- `app/Http/Controllers/EmployeeController.php`
- `app/Http/Controllers/InstitutionController.php`
- `app/Http/Controllers/PositionController.php`
- `tests/Feature/EmployeeManagementTest.php`
- `tests/Feature/EmployeeNumberConsistencyTest.php`

Tidak ada model, migration, route, view, seeder, dependency, atau schema yang diubah.

## 9. Final Verification

| Check | Hasil aktual |
|---|---|
| Targeted new regression | 6 passed, 37 assertions |
| Employee management | 3 passed, 21 assertions |
| Employee number | 16 passed, 88 assertions |
| Master data | 3 passed, 23 assertions |
| Broad module regression | 211 passed, 1,778 assertions |
| Full `php artisan test` | **253 passed, 2,038 assertions, 0 failed, 0 skipped, 33.753s test runtime** |
| `npm run build` | PASS; 56 modules, Vite build 24.08s |
| Build output | CSS 50.06 kB (gzip 9.06), JS 86.49 kB (gzip 31.55) |
| Build warning | Informational plugin timing (`laravel`/`vite:css`), tidak ada asset/build error |
| `git diff --check` | PASS, output kosong |
| Migration final | 25 Ran, 0 Pending |

## 10. Stage 2 Exit Decision

Sistem **siap masuk Stage 3 - Security Hardening** dengan catatan:

1. Tidak ada BLOCKER atau CRITICAL functional tersisa.
2. Semua HIGH functional yang ditemukan sudah diperbaiki dan diuji.
3. FB-008 tetap terbuka karena implementasi PDF merupakan feature decision, bukan bug fix minimal.
4. SEC-001 harus diaudit pada Stage 3.
5. DB-001 dan DB-002 harus ditangani terkontrol pada Stage 4; jangan melakukan cleanup data tanpa backup dan rencana migration.

Stage 3 belum dikerjakan dalam audit ini.
