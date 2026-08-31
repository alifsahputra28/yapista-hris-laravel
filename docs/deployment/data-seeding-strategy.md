# Data Seeding Strategy

Audit: 31 Agustus 2026. Repository: `D:/Devloping/yapista-hris-laravel`.
Baseline branch `main`, HEAD `985f86ed95537218a1e9aea26a95476d60abdb75`.
Environment aktual: **local**, MySQL, Laravel 13.25.0, PHP 8.3.16.
Tidak ada production target yang diakses. Database aktif hanya dibaca.

## Production Seeding

`DatabaseSeeder` sebelumnya otomatis memanggil sembilan komponen: User,
Institution, Position, Employee, Invitation, Document, Event, Participant,
Attendance. Dataset default berisi akun berpassword bawaan, 13 pegawai dan
5 event demo. `EmployeeQrTokenSeeder` tersedia sebagai entry point terpisah.

Sekarang `DatabaseSeeder` **hanya** memanggil `InstitutionSeeder` dan
`PositionSeeder`. Keduanya memakai `firstOrCreate`, tidak mengubah status,
level atau type yang sudah disesuaikan operator ketika diulang.
Default seed tidak membuat user, employee, event, attendance, undangan,
dokumen, QR, synthetic PII, atau password. Factory tidak dipanggil.

Master organisasi yang ditemukan: TK, SD, SMP, SMK, STAI, Universitas Ibnu Sina,
dan Kantor Yayasan (7 unit, 37 pasangan unit/jabatan). Ini master organisasi,
bukan target dummy cleanup. Operator tetap harus mengonfirmasi kesesuaian daftar
untuk installation target. Jabatan dicari/dibuat berdasarkan pasangan unit + nama.

Role disimpan pada `users.role`, bukan tabel roles/permissions terpisah:
`super_admin`, `hr_admin`, `panitia`, `pegawai`. Tidak diperlukan RoleSeeder baru.

Command berikut adalah **runbook**, bukan command yang sudah dijalankan pada
database aktif. Production deployment dan migration memerlukan approval terpisah:

```bash
php artisan migrate --force
php artisan db:seed --force
```

Master seeding bersifat explicit/opsional setelah review, bukan langkah otomatis
setiap deploy atau restore. Dilarang mengganti dengan seeder UAT/dev.

### Bootstrap Super Admin

Tidak ditemukan command bootstrap admin interaktif yang aman. Default seed
sekarang sengaja tidak menyediakan akun login. Invitation existing ditujukan
untuk pegawai dan bukan mekanisme menciptakan Super Admin pertama.

Sebelum production pertama, operator perlu menyetujui prosedur provisioning:
identitas admin terverifikasi, credential unik dari secret manager, password
di-hash melalui API Laravel dalam sesi console tepercaya dengan input tersembunyi,
tanpa shell/history/log plaintext, dan audit hanya ID/status. Ini rekomendasi,
bukan fitur/command baru yang diklaim tersedia. Jangan memakai UserSeeder atau
InitialUserSeeder untuk membuat admin production. Provisioning belum dilakukan.

## Staging / UAT Seeding

Gunakan database staging **baru, terpisah, kosong**; jangan clone database lokal
beserta akun/password/file runtime. Pastikan target DB/storage bukan production,
`APP_ENV=staging` (atau `uat`), dan konfigurasi cache sesuai environment target.

```bash
php artisan migrate --force
php artisan db:seed --force
php artisan db:seed --class=UatSeeder --force
```

Sebelum command terakhir, operator memasok `UAT_SEED_PASSWORD` yang acak/kuat
(minimal 12 karakter) melalui secret store/environment proses. Jangan menulis
nilainya di CLI argument, repository, dokumentasi, screenshot, atau log.
`.env.example` hanya memuat nama variable kosong. Variable lama
`EMPLOYEE_SEED_DEFAULT_PASSWORD` tidak lagi dipakai; bukan jalur provisioning
production. `config/seeding.php` membuat pembacaan secret kompatibel config cache.
Hapus secret seed dari runtime setelah provisioning dan rebuild cache secara
terkontrol di target jika sebelumnya dicache. Secret ini tidak diperlukan runtime.

Dataset baru di database kosong, diverifikasi pada SQLite testing:

| Data | Jumlah | Tujuan |
|---|---:|---|
| Unit / Jabatan | 7 / 37 | Master organisasi, bukan synthetic cleanup target |
| User | 16 | 3 operator (Super Admin/HR/Panitia) + 13 pegawai |
| Pegawai existing | 12 | NUP synthetic tepat 10 digit, verified, profil belum lengkap, QR aktif |
| Pegawai baru | 1 | `pegawai.baru@yapista.test`, NUP null, draft, tanpa QR |
| QR aktif | 12 | Acak/encrypted, melalui EmployeeQrTokenService; tidak dicetak |
| Event | 1 | `UAT Kegiatan Internal 001`, marker `[UAT FIXTURE]` |
| Participant | 11 | Satu existing fixture sengaja nonparticipant untuk negative test |
| Attendance | 0 | Baseline kosong untuk scan/manual UAT lewat UI |
| Document / Invitation | 0 / 0 | Tidak membuat metadata file fiktif atau mengirim undangan |

Daftar account dan synthetic NUP ada di `UserSeeder` dan
`database/seeders/data/employees.php`. Jangan menggunakan range NUP saja sebagai
bukti dummy. Profil/dokumen lengkap dapat diisi lewat UI dengan fixture yang
disetujui; seed ini tidak memalsukan kelengkapan atau membuat private file.
Secure QR tetap standard; legacy NUP hanya scanner attendance sesuai
`ATTENDANCE_ALLOW_LEGACY_NUP_QR` yang tidak diubah task ini.

UatSeeder menggunakan transaction: collision atau credential tidak tersedia
membatalkan perubahan seed. Event dengan nama sama namun marker berbeda ditolak,
bukan ditimpa. Rerun mempertahankan password/status akun, QR existing, jadwal/status
event dan participant yang sudah cancelled. Ia tetap menerapkan master kepegawaian
yang didefinisikan dalam fixture; jangan menggunakannya sebagai import data real.
New-employee fixture yang sudah menjalani UAT tidak di-reset kembali menjadi draft.
Untuk mengulang onboarding dari nol, gunakan DB staging disposable baru dengan
nama/approval eksplisit, bukan menghapus data shared staging secara massal.

## Development Seeding

```bash
php artisan db:seed --class=DevelopmentSeeder
```

Hanya `local` / `testing`. Memanggil master, 16 akun/13 pegawai fixture,
5 event `[DEV]`, 34 participant, 9 attendance (QR/manual). Tidak membuat
large Faker dataset. Event `[DEV]` memakai marker `[DEV FIXTURE]`; tidak
mengambil alih event lama bernama mirip. Event/attendance existing tidak di-reset.
Participant dan QR seed dibatasi pasangan account/NUP yang terdaftar di fixture,
bukan seluruh employee database. Invocation ulang tidak menduplikasi master/data.

Demo invitation lama tidak menghasilkan record pada fixture account-linked
sekarang. EmployeeDocumentSeeder tetap no-op karena metadata butuh file private
yang benar. Factory satu-satunya adalah `UserFactory`, dipakai automated tests,
tetap tersedia dan tidak dipanggil dari production/default seeder. Password
test-only factory bukan credential yang diprovision melalui production seed.

Reset local adalah proses terpisah, tidak termasuk deployment runbook. Prefer
database local disposable baru dengan nama eksplisit, migration normal, lalu
DevelopmentSeeder. Tidak ada reset/migrate:fresh yang dijalankan pada task ini.

## Seeder Matrix

Awal: 12 class seeder, 1 fixture data file, 1 factory. Sesudah: 14 class seeder,
1 helper SyntheticSeed, 1 fixture data file, 1 factory (tidak dihapus).

| Seeder | Environment | Purpose | Auto-called | Production-safe |
|---|---|---|---|---|
| DatabaseSeeder | Semua | Master-only entry | Default db:seed; UAT/dev explicit | YES |
| InstitutionSeeder | Semua | 7 Unit Kerja | DatabaseSeeder | YES, review master |
| PositionSeeder | Semua | 37 Unit/Jabatan | DatabaseSeeder | YES, review master |
| UatSeeder (baru) | local/testing/staging/uat | Compact acceptance fixture | Tidak dari default | NO, guarded |
| DevelopmentSeeder (baru) | local/testing | Demo scenarios lama | Tidak dari default | NO, guarded |
| UserSeeder | local/testing/staging/uat | 3 operator synthetic | UAT/dev only | NO, guarded |
| InitialUserSeeder | local/testing/staging/uat | Alias UserSeeder | Tidak | NO, inherited guard |
| EmployeeSeeder | local/testing/staging/uat | 13 onboarding fixtures | UAT/dev only | NO, run + seedRows guarded |
| EmployeeQrTokenSeeder | local/testing/staging/uat | QR fixture eligible only | Tidak, EmployeeSeeder pakai service | NO, guarded |
| EmployeeInvitationSeeder | local/testing | Legacy demo, saat ini 0 record | Dev only | NO, guarded |
| EmployeeDocumentSeeder | local/testing | No-op, tidak membuat file palsu | Dev only | NO, guarded |
| EventSeeder | local/testing | 5 demo events berprefix [DEV] | Dev only | NO, guarded |
| EventParticipantSeeder | local/testing | Participant fixture-only | Dev only | NO, guarded |
| EventAttendanceSeeder | local/testing | 9 attendance demo | Dev only | NO, guarded |

Guard allowlist menolak production, `prod`, dan environment tak dikenal.
`--force` tidak melewati guard. Seeder component langsung juga dilindungi.
Password baru wajib explicit; existing credentials tidak dirotasi otomatis.

## Current Database Audit / Cleanup Dry Run

**DRY RUN ONLY. Tidak ada seeder atau cleanup yang dijalankan ke MySQL lokal.**
Klasifikasi adalah kandidat inventory untuk review, bukan izin delete.

| Entity | Total aktual | Synthetic terkonfirmasi | Unknown / protected |
|---|---:|---:|---:|
| Unit | 7 | 0 | 7 master organisasi, dipertahankan |
| Jabatan | 37 | 0 | 37 master organisasi, dipertahankan |
| Users | 19 | 16 | 3 |
| Employees | 18 | 14 | 4 |
| Events | 5 | 5 | 0 |
| Participants | 34 | 34 | 0 |
| Attendances | 9 | 9 | 0 |
| QR tokens | 14 | 12 | 2 |
| Invitations | 3 | 0 | 3 |
| Documents (DB) | 0 | 0 | 0 |
| Family / education / certifications / administration | 0 / 0 / 0 / 0 | 0 | 0 |

Production-like master records = 44. Unknown primary records = 7 (3 users +
4 employees); dengan QR/invitation child = 12 records. Tidak menghitung tabel
infrastruktur seperti sessions/cache/jobs sebagai synthetic business records.

Evidence classification:
- Users ID 1-16: exact fixture email **dan** role cocok dengan UserSeeder/data file;
  bukan aturan delete berdasarkan rentang ID.
- Employees ID 1-13: exact fixture account, NUP/null, nama, dan hubungan user cocok.
- Employee ID 18: exact marker `UAT UX Unit Jabatan 20260831`, tanpa user/NUP,
  didukung dokumentasi browser UAT `docs/uat/employee-work-fields-2026-08-31.md`.
- Events ID 1-5: exact kombinasi nama/deskripsi EventSeeder sebelum perubahan,
  dan creator dari known synthetic users. Bukan prefix saja.
- 34 participants / 9 attendances: **kedua** ujung relasi berada pada fixture
  terkonfirmasi; cross-boundary participant/attendance ditemukan 0.
- Unknown users ID 17-19; employees ID 14-17 tetap dilindungi. Tiga invitation
  dan dua QR milik record di luar fixture juga tidak masuk proposal cleanup.

Proposal count (belum disetujui): users 16, employees 14, events 5,
participants 34, attendances 9, QR 12, documents 0, files 0.
Sebelum delete akun, review seluruh reference audit seperti verified_by,
profile_reviewed_by, created_by dan scanned_by, sessions, password-reset tokens.
Jangan menghilangkan attribution pada record unknown hanya karena FK nullOnDelete.
Inventory harus diulang jika operator melakukan perubahan setelah snapshot ini.

**16 akun fixture lokal masih cocok dengan default password lama** berdasarkan
pemeriksaan hash read-only. Nilainya tidak dicatat. Source tidak lagi mempunyai
default tersebut, tetapi akun existing sengaja tidak dirotasi. Jangan expose/clone
DB ini ke staging. Gunakan fresh isolated staging + secret baru, atau minta
persetujuan rotasi/cleanup terpisah sebelum memakai database ini di jaringan.

### Storage / Artifact

- Private employee-documents: 5 file, referenced 0, unreferenced UNKNOWN 5.
- Private employee-photos: 1 file, referenced 0, unreferenced UNKNOWN 1.
- Private `employees/`: 0 file. Public runtime disk: 0 file selain gitignore.
- Tidak ada synthetic document/file yang dapat dikonfirmasi aman dihapus dari
  metadata saat ini. Unreferenced **tidak berarti** dummy; enam file tetap private.
- Quarantine legacy tidak dibuka, diubah, dipindahkan, atau dihapus. Enam file
  legacy + manifest dari runbook lama bukan objek cleanup task ini.
- `git ls-files` tidak memuat SQL/dump/SQLite/XLSX/CSV runtime/private uploads.
  Tracked storage hanya `.gitignore`; `.env.example` berisi secret kosong.
- `.env`, SQLite lokal, private uploads/quarantine, compiled views/logs,
  node_modules, vendor, public/hot, public/build dan public/storage di-ignore.
  Git ignore bukan jaminan aman untuk zip seluruh folder: buat artifact dari
  tracked source ter-review + verified build assets, bukan copy workspace.
- Dependency runtime dipasang dari lockfile di build/release host. Storage baru
  dibuat kosong dan persistent secara terpisah; jangan menyalin runtime lokal,
  exports, audit helper `.codex`, backup, atau cache/hot marker.

### Cleanup After Explicit Inventory Approval

Tidak dibuat generic destructive cleanup command. `uat:cleanup` **belum ada**.
Tidak ada command delete yang otomatis dipanggil oleh seed.

1. Operator menyetujui exact ID inventory non-production dan target DB/storage.
2. Ambil consistent MySQL dump + private-file snapshot sesuai
   `backup-restore-runbook.md`, di luar public/Git; catat timestamp, bytes, SHA-256.
3. Re-check fixture fingerprints, FK dan reference unknown sebelum transaction.
4. Urutan sesuai schema: attendance (restrict employee), participant, invitation
   (restrict employee), document/child metadata, QR, employee, event, lalu user
   setelah seluruh attribution/session reference ditinjau. Gunakan exact ID sets,
   bukan ID threshold/prefix wildcard. Jangan disable foreign-key checks.
5. File hanya boleh dibersihkan setelah provenance, DB commit dan snapshot
   dikonfirmasi; physical file bukan bagian transaction SQL. UNKNOWN/quarantine
   tetap dipertahankan. Review hasil aggregate setelah operasi.

Cleanup performed: **NO**. Semua record/file removed: **0**.
Real/master records removed: **0**. Backup baru tidak dibuat karena tidak ada
mutation cleanup; backup wajib sebelum eksekusi inventory yang nanti disetujui.

## Verification / Remaining Gates

| Check | Actual result |
|---|---|
| Targeted seed/employee/role/master/QR/event/attendance/import/verification | 127 PASS / 1,084 assertions |
| Full php artisan test #1 | 349 total: 348 PASS, 1 FAIL, 0 skipped; 3,036 assertions |
| Full php artisan test #2 | 349 total: 348 PASS, 1 FAIL, 0 skipped; 3,036 assertions |
| Production and direct seeder guards, including --force | PASS in isolated SQLite :memory: |
| Default master / UAT / dev idempotency | PASS |
| NUP/unit-position/QR/participant fixture invariants | PASS |
| npm run build | PASS, Vite 8.0.16, 57 modules |
| Composer audit --locked --no-dev | 0 advisories |
| npm audit / npm audit --omit=dev | 0 / 0 vulnerabilities |
| migrate:status | 26 Ran / 0 Pending; no migration added/run on active DB |
| Current DB unit/position mismatch | 0 |
| Current DB duplicate participant/attendance groups | 0 / 0 |
| Current DB multiple active QR / inactive with QR / eligible missing QR | 0 / 0 / 0 |
| employee-security:verify-nik | PASS, 0 issues |
| git diff --check | PASS |

Both full runs fail the same pre-existing test:
`tests/Feature/DashboardInsightsTest.php:31`,
`test_dashboard_uses_real_employee_event_and_attendance_aggregates`.
View-data aggregate predicate returns false. Dashboard source/test unchanged by
this task; failure already recorded in preceding UI work. No assertion disabled,
no test skipped, no dashboard repair slipped into seeder cleanup.

Working tree was dirty at start: prior employee/scanner UI, JS, tests and UAT
documentation are preserved and excluded from this task's selective commit.
Full tests/build exercise this combined working tree, **not** an independently
validated new release candidate. No tag change, push, deploy or production action.

Remaining actions: resolve known DashboardInsights regression; review/commit
the separate UI work and finish its pending browser UAT; build/retest a clean
candidate; provision a fresh isolated staging target and operator secret. Current
DB/default credentials and unclassified runtime files must not be copied there.
Production bootstrap provisioning needs an approved operational procedure.

**NOT READY FOR STAGING - DATA/SEEDER CLEANUP REQUIRED**
Seeder separation itself is implemented; this report does not waive release
regression gates or approve deletion of current local data.

## What Not To Run In Production

- UatSeeder, DevelopmentSeeder or any synthetic component, including
  UserSeeder/InitialUserSeeder/EmployeeSeeder/EmployeeQrTokenSeeder.
- `migrate:fresh`, `migrate:refresh`, `db:wipe`, TRUNCATE, wildcard deletes,
  disabled FK checks, or local-reset recipes on an existing database.
- Factory-driven real provisioning, seed default credentials, cloning local
  uploads/DB, or using test-only passwords as operator production credentials.
- Any deployment/migration/DNS switch or mass import/invitation without separate
  explicit approval. This task authorizes none of those actions.
