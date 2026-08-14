# YAPISTA HRIS Database & Data Integrity Audit

Audit date: 2026-08-14 (Asia/Jakarta)

## 1. Baseline

- Branch: `main`
- HEAD: `3120bfbc0e90de9dc8006d856039c2ca8319c4bd`
- Working tree: tidak bersih karena perubahan Tahap 2 dan Tahap 3 yang belum di-commit; perubahan tersebut dipertahankan.
- Database: MySQL 8.4.3, InnoDB, database charset `utf8mb4`, database collation `utf8mb4_0900_ai_ci`.
- Tabel aplikasi umumnya menggunakan `utf8mb4_unicode_ci`.
- Application timezone: `Asia/Jakarta`.
- Database server menggunakan `SYSTEM`; pengukuran `NOW()` menunjukkan offset UTC+07:00.
- Migration awal: 25 Ran, 0 Pending.
- Test awal Tahap 4: 261 passed, 2,095 assertions, 0 failed, 0 skipped.
- `git diff --check` awal: PASS.

Audit dilakukan dengan query agregat. NIK, KK, rekening, BPJS, NPWP, password, QR token mentah, dan path dokumen privat tidak dicetak.

## 2. Schema Inventory

Sebanyak 22 tabel diperiksa. Semua tabel menggunakan primary key sesuai fungsi tabel; tabel pivot/core menggunakan foreign key dan unique key yang dirinci pada bagian berikutnya. Tidak ada model core yang menggunakan `SoftDeletes`.

| Table | Primary Key | Fungsi | Unique/Index Penting | Catatan |
|---|---|---|---|---|
| `users` | `id` | Akun autentikasi | unique `email` | Role aktual valid: super_admin, hr_admin, panitia, pegawai |
| `institutions` | `id` | Unit kerja | unique `name` (baru) | Nama unik global |
| `positions` | `id` | Jabatan per unit | unique `(institution_id,name)` (baru) | `institution_id` sekarang wajib |
| `employees` | `id` | Data inti pegawai | unique `employee_number`, `email`, `nik_lookup`, `user_id` | NUP tetap nullable untuk pegawai baru |
| `employee_invitations` | `id` | Undangan onboarding | unique `invitation_code` | Employee bersifat restrict |
| `employee_family_members` | `id` | Anggota keluarga | FK/index employee | Data berulang per employee |
| `employee_educations` | `id` | Pendidikan | FK/index employee | Maksimal satu tertinggi dijaga aplikasi |
| `employee_certifications` | `id` | Sertifikasi | FK/index employee | Validasi tanggal dijaga aplikasi |
| `employee_administrative_details` | `id` | Bank/pajak/BPJS | unique `employee_id` | One-to-one enforced di DB |
| `employee_documents` | `id` | Metadata dokumen privat | unique `(employee_id,document_type,document_slot)` | Relasi pendidikan/sertifikasi restrict |
| `employee_qr_tokens` | `id` | Token QR revocable | unique `token_hash`; index `(employee_id,is_active)` | Satu token aktif dijaga service/transaction |
| `events` | `id` | Kegiatan | index tanggal, target, status | Creator boleh null |
| `event_participants` | `id` | Peserta kegiatan | unique `(event_id,employee_id)` | Duplicate peserta ditolak DB |
| `event_attendances` | `id` | Kehadiran kegiatan | unique `(event_id,employee_id)` | Histori event sekarang `RESTRICT` |
| `migrations` | `id` | Riwayat migration | batch/name | Framework |
| `sessions` | `id` | Session | user/last_activity indexes | Framework |
| `password_reset_tokens` | `email` | Reset password | key email | Framework |
| `cache` | `key` | Cache | primary key | Framework |
| `cache_locks` | `key` | Cache lock | primary key | Framework |
| `jobs` | `id` | Queue | queue index | Framework |
| `job_batches` | `id` | Queue batch | primary key | Framework |
| `failed_jobs` | `id` | Failed queue jobs | unique UUID | Framework |

## 3. Foreign Key Inventory

| Table.Column | References | ON DELETE | Validasi/Audit |
|---|---|---|---|
| `positions.institution_id` | `institutions.id` | RESTRICT | Diubah dari nullable/SET NULL; 0 jabatan tanpa unit |
| `employees.user_id` | `users.id` | SET NULL | Unique baru menegakkan satu akun-satu pegawai |
| `employees.institution_id` | `institutions.id` | RESTRICT | Tepat untuk master data aktif |
| `employees.position_id` | `positions.id` | RESTRICT | Tepat; 0 unit-position mismatch |
| `employees.verified_by` | `users.id` | SET NULL | Legacy boleh null |
| `employees.profile_reviewed_by` | `users.id` | SET NULL | Reviewer yang dihapus tidak menghapus profil |
| `employee_invitations.employee_id` | `employees.id` | RESTRICT | Undangan tidak menghapus employee |
| `employee_invitations.created_by` | `users.id` | SET NULL | Audit creator boleh menjadi null |
| `employee_family_members.employee_id` | `employees.id` | CASCADE | Diterima sebagai child profile lifecycle |
| `employee_educations.employee_id` | `employees.id` | CASCADE | Diterima sebagai child profile lifecycle |
| `employee_certifications.employee_id` | `employees.id` | CASCADE | Diterima sebagai child profile lifecycle |
| `employee_administrative_details.employee_id` | `employees.id` | CASCADE | Diterima sebagai one-to-one child |
| `employee_documents.employee_id` | `employees.id` | CASCADE | Metadata child; employee di aplikasi dinonaktifkan, bukan dihapus |
| `employee_documents.education_id` | `employee_educations.id` | RESTRICT | Mencegah relasi dokumen terputus |
| `employee_documents.certification_id` | `employee_certifications.id` | RESTRICT | Mencegah relasi dokumen terputus |
| `employee_qr_tokens.employee_id` | `employees.id` | CASCADE | Diterima; employee operasional tidak dihapus oleh controller |
| `employee_qr_tokens.created_by` | `users.id` | SET NULL | Creator boleh null untuk legacy/system repair |
| `events.created_by` | `users.id` | SET NULL | Kegiatan bertahan saat akun creator hilang |
| `event_participants.event_id` | `events.id` | CASCADE | Draft tanpa histori dapat dihapus bersama participant |
| `event_participants.employee_id` | `employees.id` | CASCADE | Tidak menghapus attendance karena FK attendance restrict employee |
| `event_attendances.event_id` | `events.id` | RESTRICT | Diubah dari CASCADE untuk menjaga histori kehadiran |
| `event_attendances.employee_id` | `employees.id` | RESTRICT | Menjaga histori pegawai |
| `event_attendances.qr_token_id` | `employee_qr_tokens.id` | SET NULL | Attendance tetap ada saat token tidak tersedia |
| `event_attendances.scanned_by` | `users.id` | SET NULL | Attendance tetap ada saat scanner user hilang |

Seluruh orphan check pada relasi core menghasilkan 0. Perubahan destructive pada employee tidak tersedia pada aplikasi; aksi delete employee melakukan deactivation dan revoke QR.

## 4. Unique Constraint Inventory

Constraint yang sudah tersedia sebelum Tahap 4:

- `users.email`.
- `employees.email`.
- `employees.employee_number`.
- `employees.nik_lookup`.
- Kolom legacy `employees.nik` dan `employees.nup` (nullable; tidak digunakan sebagai source aktif).
- `employee_administrative_details.employee_id`.
- `employee_documents(employee_id,document_type,document_slot)`.
- `employee_qr_tokens.token_hash`.
- `event_participants(event_id,employee_id)`.
- `event_attendances(event_id,employee_id)`.

Constraint baru:

- `institutions_name_unique` pada `institutions(name)`.
- `positions_institution_name_unique` pada `positions(institution_id,name)`.
- `employees_user_id_unique` pada `employees(user_id)`; MySQL tetap mengizinkan beberapa NULL.

Precondition migration memeriksa duplicate unit ter-normalisasi, duplicate jabatan per unit ter-normalisasi, duplicate link user, dan jabatan tanpa unit. Migration berhenti dengan pesan aman jika data tidak memenuhi syarat.

## 5. Employee/NUP Integrity

### Sebelum remediation

| Metric | Count |
|---|---:|
| Total employee | 14 |
| NUP null | 1 |
| NUP valid 10 digit | 13 |
| NUP invalid | 0 |
| Duplicate NUP | 0 |
| Legacy `nup` non-null | 0 |
| Legacy `foundation_registry_number` non-null | 0 |
| Valid NUP tetapi bukan verified | 1 |
| Verified tanpa NUP valid | 0 |

### Sesudah remediation

- Total employee tetap 14.
- 13 employee verified dan memiliki NUP valid.
- 1 employee tetap draft tanpa NUP; workflow employee baru tidak diubah.
- Valid NUP tetapi bukan verified: 0.
- Verified tanpa NUP valid: 0.
- Invalid dan duplicate NUP: 0.
- `employee_number` tetap plaintext, nullable untuk draft, unique, dan menjadi satu-satunya source of truth NUP.

Format 10 digit tetap dijaga validation/model karena CHECK lintas database tidak ditambahkan. Unique NUP tetap ditegakkan oleh database.

## 6. Verification Integrity

Sebelum repair terdapat 12 employee legacy berstatus verified tanpa `verified_at`, ditambah 1 employee NUP-valid yang belum verified.

Repair menggunakan aturan berikut:

- Employee NUP-valid yang belum verified menjadi `verified` dengan `verified_at = now()`.
- Employee legacy yang sudah verified tetapi tanpa timestamp menggunakan `created_at` sebagai timestamp legacy yang paling konservatif.
- `verified_by` tidak diisi secara fiktif. Nilai tetap null untuk record legacy/system repair.
- Employee tanpa NUP tetap pada status workflow aktual (`draft`, `submitted`, atau `rejected`); tidak dipaksa menjadi draft.

Hasil akhir: verified tanpa timestamp 0 dan valid-NUP/non-verified 0.

## 7. Institution & Position Integrity

| Check | Sebelum | Sesudah |
|---|---:|---:|
| Unit name null/empty | 0 | 0 |
| Duplicate unit normalized | 0 | 0 |
| Position tanpa unit | 0 | 0 |
| Duplicate position dalam unit | 0 | 0 |
| Nama position sama di unit berbeda | 10 kelompok | Tetap diizinkan |
| Employee unit-position mismatch | 0 | 0 |

Application validation tetap memberikan pesan ramah. `UniqueConstraintViolationException` sekarang ditangani pada create/update unit dan jabatan agar race tidak mengekspos SQL exception atau menghasilkan 500.

## 8. Sensitive Data Integrity

- Legacy plaintext NIK aktif: 0.
- `nik_encrypted` tanpa `nik_lookup`: 0.
- `nik_lookup` tanpa ciphertext: 0.
- NIK decrypt failure pada audit: 0.
- `nik_lookup` tetap unique/indexed; ciphertext tidak digunakan sebagai lookup.
- Decrypt failure pada nomor KK, NIK keluarga, rekening, pajak, BPJS, nomor ijazah, dan nomor sertifikat: 0.
- Tidak ada sensitive value yang dicetak selama audit.
- Tidak ada index baru yang menggunakan NIK mentah atau QR raw token.

## 9. QR Integrity

| Metric | Sebelum | Sesudah |
|---|---:|---:|
| Active QR | 12 | 13 |
| Revoked QR | 0 | 0 |
| Employee dengan lebih dari satu active QR | 0 | 0 |
| Active QR untuk employee ineligible | 0 | 0 |
| Eligible employee tanpa active QR | 0 | 0 |
| Active token dengan `revoked_at` | 0 | 0 |
| Inactive token tanpa `revoked_at` | 0 | 0 |
| Hash/cipher pair tidak lengkap | 0 | 0 |
| Orphan QR token | 0 | 0 |

Repair membuat satu QR melalui `EmployeeQrTokenService`; tidak ada logic token kedua di command. Satu active QR per employee tetap deliberate application enforcement menggunakan transaction dan row lock. Conditional unique index MySQL tidak dipaksakan karena akan menambah kompleksitas schema yang tidak proporsional.

## 10. Profile Related Tables

Data development saat audit:

- Family members: 0.
- Educations: 0.
- Certifications: 0.
- Administrative details: 0.

Audit schema dan data menghasilkan:

- Orphan family/education/certification/administrative: 0.
- Duplicate administrative detail per employee: 0; unique DB sudah tersedia.
- Multiple highest education: 0.
- Invalid education year order: 0.
- Certification expiry sebelum issue: 0.
- Invalid family relationship value: 0.

Maksimal satu highest education tetap dijaga pada service/application. Partial unique MySQL tidak ditambahkan.

## 11. Documents

- Metadata document: 0.
- Metadata dengan file fisik hilang: 0.
- Legacy public document file: 0.
- File privat tanpa metadata: 0.
- Education/certification relation employee mismatch: 0.
- Dokumen yang sekaligus menunjuk education dan certification: 0.
- Photo metadata/private/public/missing: seluruhnya 0 pada data development saat audit.

Private document storage dan private employee photo strategy Tahap 3 tidak diubah. Tidak ada file sensitif yang dibuka atau dipindahkan.

## 12. Events / Participants / Attendance

Data development: 5 events, 34 participants, dan 9 attendance.

| Check | Sebelum | Sesudah |
|---|---:|---:|
| Invalid event status/target/time | 0 | 0 |
| Orphan event creator tak terduga | 0 | 0 |
| Duplicate participant | 0 | 0 |
| Invalid participant status | 0 | 0 |
| Duplicate attendance | 0 | 0 |
| Attendance tanpa participant | 0 | 0 |
| Attendance untuk participant cancelled | 0 | 0 |
| Invalid attendance method/status | 0 | 0 |
| Missing `scanned_at` | 0 | 0 |
| QR attendance tanpa token | 0 | 0 |
| Manual attendance dengan QR token | 2 | 0 |
| Timestamp attendance di luar tanggal event | 0 | 0 |

Root cause dua manual attendance adalah seeder yang selalu mengisi `qr_token_id`. Seeder diperbaiki tetapi tidak dijalankan pada development. Dua row development diperbaiki command dengan mengosongkan hanya `qr_token_id`; histori attendance lain tidak berubah.

FK `event_attendances.event_id` diubah dari CASCADE ke RESTRICT. Controller juga menolak delete event yang memiliki attendance dan menangani race FK secara aman. Draft/cancelled event tanpa histori tetap mengikuti flow delete yang ada.

## 13. Orphan Audit

Seluruh aggregate orphan check berikut menghasilkan 0:

- employee ke user, institution, position, verifier, dan profile reviewer;
- family, education, certification, administrative detail, dan document ke employee;
- document ke education/certification;
- QR token ke employee/creator;
- participant ke event/employee;
- attendance ke event/employee/token/scanner.

## 14. Duplicate Audit

Seluruh duplicate check yang melanggar business rule menghasilkan 0:

- email user;
- link user ke employee;
- NUP employee;
- unit ter-normalisasi;
- jabatan ter-normalisasi dalam unit yang sama;
- administrative detail per employee;
- participant per event+employee;
- attendance per event+employee;
- active QR lebih dari satu per employee.

Nama jabatan sama di unit berbeda bukan duplicate dan tetap diizinkan.

## 15. Migration Changes

Migration baru:

`2026_08_14_000000_enforce_core_data_integrity_constraints.php`

Perubahan:

1. Unique `institutions(name)`.
2. Composite unique `positions(institution_id,name)`.
3. Unique nullable `employees(user_id)`.
4. `positions.institution_id` menjadi NOT NULL.
5. FK position ke institution menjadi RESTRICT.
6. FK attendance ke event menjadi RESTRICT.

Migration bersifat forward-only, memiliki precondition data, dan `down()` yang masuk akal. Fresh isolated SQLite migration dijalankan oleh test suite. Rollback development tidak dijalankan.

Migration development berhasil diterapkan dalam batch 2. Status akhir: 26 Ran, 0 Pending.

## 16. Data Remediation

Command baru:

`php artisan employees:repair-integrity`

Mode:

- Default/`--dry-run`: hanya audit agregat.
- `--commit`: repair eksplisit.
- `--dry-run` dan `--commit` bersamaan ditolak.

Command menggunakan transaction, row lock, model business helper, dan `EmployeeQrTokenService`. Output hanya count, tanpa NUP/token/data sensitif.

Dry-run development sebelum repair:

- Valid NUP not verified: 1.
- Verified without verified_at: 12.
- Eligible without active QR: 0 (employee mismatch belum eligible sebelum status diperbaiki).
- Ineligible with active QR: 0.
- Manual attendance linked to QR: 2.

Commit mengubah:

- 1 employee menjadi verified.
- 12 employee legacy mendapat `verified_at` dari `created_at`.
- 1 QR baru dibuat melalui service untuk employee yang baru menjadi eligible.
- 2 attendance manual dikosongkan `qr_token_id`-nya.
- 0 QR dicabut.

Total row development yang ditulis: 16 (13 employee, 1 QR baru, 2 attendance). Tidak ada record dihapus. Eksekusi commit kedua mengubah 0 row dan seluruh check bernilai 0.

Backup sebelum remediation:

- Lokasi di luar repository: `D:\Backups\yapista-hris\stage4-pre-integrity-20260814-085449.sql`.
- Size: 60,428 bytes.
- SHA-256: `9AE0CFD9AA850BEDCAE27DDC22B27055C2A30E9DCF2AD956339A0F638A67D678`.
- Dump memiliki header MySQL dan completion marker; tidak disimpan di Git/public.

## 17. Regression Tests

File baru:

- `tests/Feature/DatabaseDataIntegrityTest.php`

Cakupan baru:

- inventory unique indexes;
- duplicate unit di DB;
- duplicate jabatan per unit dan same-name lintas unit;
- jabatan wajib memiliki unit;
- satu user hanya dapat terkait ke satu employee;
- repair dry-run tidak mengubah data;
- repair commit aman dan idempotent;
- valid NUP menjadi verified dan mendapat QR;
- employee tanpa NUP tetap draft;
- stale QR employee nonaktif dicabut;
- manual attendance tidak menyimpan QR reference;
- event dengan histori attendance tidak dapat dihapus pada application dan DB layer.

Test yang diperluas:

- `DatabaseSeederIdempotencyTest`: manual attendance hasil seeder tidak memiliki QR reference.
- `EmployeeImportTest`: invitation code importer menggunakan 32 karakter acak seperti flow undangan aktif.

Hasil sementara sebelum final run:

- `DatabaseDataIntegrity`: 8 passed, 55 assertions.
- Regression terfokus (Seeder, Import, FunctionalBug, EventManagement, EventAttendance): 33 passed, 243 assertions.
- Full suite pre-development-migration: 269 passed, 2,152 assertions.

Hasil final:

- Full suite: 269 passed, 2,152 assertions, 0 failed, 0 skipped.
- Vite: 56 modules transformed; build sukses dalam 3.47 detik.
- Output utama: CSS 50.06 kB dan JavaScript 86.49 kB.
- Warning build hanya plugin timing (`laravel` 76%, `vite:css` 21%); tidak ada unresolved asset atau build error.
- `git diff --check`: PASS.

## 18. Remaining Findings

| ID | Severity | Table/Area | Finding | Affected | Fix | Verification | Status |
|---|---|---|---:|---:|---|---|---|
| DBI-001 | HIGH | Employee verification | NUP-valid tetapi belum verified | 1 | Repair command mengubah status/timestamp dan membuat QR | Post-repair count 0 | FIXED |
| DBI-002 | MEDIUM | Employee verification | Verified legacy tanpa `verified_at` | 12 | Gunakan `created_at`; `verified_by` tidak difabrikasi | Post-repair count 0 | FIXED |
| DBI-003 | HIGH | Institutions | Duplicate hanya dicegah application layer | 0 duplicate | Unique DB + race handling | Index aktif, test duplicate lulus | FIXED |
| DBI-004 | HIGH | Positions | Duplicate per unit hanya dicegah application layer | 0 duplicate | Composite unique DB + race handling | Index aktif, same-name lintas unit tetap allowed | FIXED |
| DBI-005 | MEDIUM | Positions | Unit nullable dan SET NULL bertentangan dengan model bisnis | 0 null | NOT NULL + RESTRICT | FK aktif, test null gagal sesuai target | FIXED |
| DBI-006 | HIGH | Attendance history | Delete event dapat cascade attendance | 0 corruption saat ini | FK RESTRICT + controller guard | HTTP dan direct DB test lulus | FIXED |
| DBI-007 | MEDIUM | Attendance seeder | Manual attendance menyimpan QR reference | 2 | Repair rows dan koreksi seeder | Post-repair count 0, seeder test lulus | FIXED |
| DBI-008 | MEDIUM | Employee/user | One-to-one hanya di Eloquent | 0 duplicate | Unique nullable `employees.user_id` | Direct DB duplicate test lulus | FIXED |
| DBI-009 | LOW | Employee import | Invitation code import masih 8 karakter | Future rows | Samakan menjadi 32 karakter random | Import test regex lulus | FIXED |
| DBI-010 | INFO | QR lifecycle | Satu active QR tidak memiliki conditional unique DB | 0 violation | Transaction + row lock service dipertahankan | Audit/test lifecycle 0 violation | ACCEPTED |
| DBI-011 | LOW | Legacy columns | `nup`, `foundation_registry_number`, dan NIK plaintext legacy masih ada di schema | 0 active values | Tidak di-drop pada finalization | Code/search dependency audit berikutnya | DEFERRED |
| DBI-012 | INFO | Status columns | Status utama menggunakan string, bukan DB enum/CHECK | 0 invalid values | Application validation dipertahankan | Aggregate invalid count 0 | ACCEPTED |

Tidak ada BLOCKER, CRITICAL, atau unresolved HIGH finding. Database siap diteruskan ke Tahap 5 setelah final regression tetap lulus. Tahap 5 tidak dikerjakan dalam dokumen ini.
