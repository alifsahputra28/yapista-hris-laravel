# YAPISTA HRIS Finalization Baseline

## 1. Timestamp Audit

- Waktu lokal audit: 2026-08-12 08:58:26 sampai 09:11:08 +07:00 (Asia/Jakarta).
- Scope: Stage 1 - Feature Freeze & Baseline.
- Mode: read, audit, safe checks, dan dokumentasi. Tidak ada bug fix atau perubahan business logic.
- Feature freeze aktif untuk Authentication, Dashboard, Master Data, Employee, Verification, Invitation, Profile, Documents, NIK Security, QR, E-Card, Event, Participant, Attendance, Reports, Import/Export, dan Employee Mobile UI.

## 2. Git

- Repository: `D:\Devloping\yapista-hris-laravel`.
- Branch aktif: `main` (tracking `origin/main`).
- HEAD: `8f64bbce7463813bd5935c2422676a5de4714112`.
- HEAD subject: `refactor: update report and verification views for improved UI consistency`.
- Lima commit terakhir:
  - `8f64bbc` refactor: update report and verification views for improved UI consistency
  - `5a205c6` feat: implement QR code functionality for employee attendance
  - `52ea4b4` Refactor employee profile tests and add comprehensive submission tests
  - `68b79d3` feat: add family member management to employee profile
  - `2d75ccc` feat: add family member management to employee profile
- Working tree: **tidak clean**.
- Staged changes: 0.
- Tracked changes: 49 file (47 modified, 2 deleted).
- Untracked: 24 file (diringkas Git sebagai 22 entry karena direktori).
- `git diff --stat`: 49 file, 2.718 insertion, 1.100 deletion.
- `git diff --check`: exit 0; tidak ada whitespace error. Ada warning normalisasi CRLF ke LF pada empat file, tanpa perubahan otomatis.

### Perubahan Existing per Kelompok

| Kelompok | Kondisi | Contoh bukti |
|---|---:|---|
| Application/backend | Modified dan untracked | Auth redirect, Employee, Attendance, Participant, Dashboard, Activity, Import services/controllers |
| UI | Modified dan untracked | Mantis layout, login, dashboard, employee mobile UI, E-Card, filter, import modal |
| Migration | Tidak berubah | Seluruh 25 migration tracked dan berstatus Ran |
| Tests | 2 modified, 6 untracked | QR, login entry point, dashboard insight, E-Card, import, employee UI, filter |
| Config/bootstrap | Modified | `bootstrap/app.php`, `routes/web.php` |
| Dependency | Modified | `composer.json`, `composer.lock` |
| Documentation | Tidak ada sebelum audit | Dokumen ini adalah dokumentasi baseline pertama |
| Generated/build | Tidak tercatat sebagai perubahan Git | `npm run build` selesai tanpa menambah tracked diff |

Tracked yang berubah mencakup controller auth/employee/attendance/participant/ID Card/profile, `bootstrap/app.php`, dependency files, `public/assets/css/yapista-ui.css`, 31 view/layout/partial, `routes/web.php`, dan dua test. Dua partial wizard yang terhapus adalah `employment-summary.blade.php` dan `progress-card.blade.php`.

Untracked aktif mencakup:

- Dashboard controller/service dan test insight.
- Employee activity controller dan dua view kegiatan pegawai.
- Employee import controller/request/services/support, dua component import, dan test import.
- `UserRedirector` dan test login entry point.
- Component E-Card dan test E-Card.
- Active filter chip dan test filter.
- Employee bottom navigation, account view, dan test self-service UI.

Tidak ada reset, restore, stash, commit, push, atau clean yang dijalankan.

## 3. Runtime

| Komponen | Baseline |
|---|---|
| PHP | 8.3.16 ZTS x64 |
| Laravel | 13.7.0 |
| Composer | 2.8.5 |
| Node.js | 22.13.1 |
| npm | 10.9.2 |
| Vite | 8.0.10 |
| APP_ENV | local |
| APP_DEBUG | true |
| Application timezone | UTC |
| OS timezone audit | Asia/Jakarta (+07:00) |
| Database driver | MySQL |
| Session | database; encrypt false; HTTP-only true; SameSite lax; secure cookie tidak diset di `.env` |
| Cache / Queue | database / database |
| Filesystem default | local private root (`storage/app/private`) |
| Public storage link | linked ke `storage/app/public` |

Konfigurasi secret diperiksa hanya sebagai status:

- `APP_KEY`: configured.
- `EMPLOYEE_NIK_LOOKUP_KEY`: configured.
- Nilai secret tidak dicetak atau disalin.
- `.env.example` menyediakan placeholder kosong untuk kedua key dan default SQLite, sedangkan environment development aktual memakai MySQL.

## 4. Migration Status

- Total migration: 25.
- Ran: 25.
- Pending: 0.
- Batch tertinggi/terakhir: 1.
- Migration terbaru yang aktif:
  - extended identity/address, emergency contact;
  - `employee_family_members`, `employee_educations`, `employee_certifications`;
  - `employee_administrative_details` dan profile review fields;
  - document relation extensions;
  - secure NIK fields;
  - secure QR token fields.
- Tidak ada migration import/export karena implementasi tersebut tidak membutuhkan schema baru.
- Tidak ada migration yang dijalankan pada audit ini.

### Tabel Utama dan Relasi

| Tabel | Fungsi dan relasi utama |
|---|---|
| `users` | Akun, role, status; has-one Employee; creator/verifier/scanner relations |
| `institutions` | Unit kerja; has-many Position dan Employee |
| `positions` | Jabatan; belongs-to Institution; has-many Employee |
| `employees` | Master pegawai; belongs-to User/Institution/Position; pusat profil, NUP, verification, NIK, QR, event |
| `employee_invitations` | Onboarding berbasis kode; belongs-to Employee dan creator User |
| `employee_documents` | Dokumen privat; belongs-to Employee, optional Education/Certification |
| `employee_family_members` | Daftar keluarga dinamis; belongs-to Employee |
| `employee_educations` | Riwayat pendidikan; belongs-to Employee; has-many Document |
| `employee_certifications` | Sertifikasi; belongs-to Employee; has-many Document |
| `employee_administrative_details` | Bank, pajak, BPJS; one-to-one Employee |
| `employee_qr_tokens` | Token QR revocable; belongs-to Employee/creator; has-many Attendance |
| `events` | Kegiatan; belongs-to creator; has-many Participant dan Attendance |
| `event_participants` | Pivot peserta dan status; unique event + employee |
| `event_attendances` | Kehadiran; unique event + employee; links QR token/scanner |

## 5. Roles

| Role aktual | Default landing | Akses utama | Middleware |
|---|---|---|---|
| `super_admin` | `dashboard` | Dashboard, master data, employee, verification, event, report, import, QR admin | `auth`, `role:super_admin,hr_admin` |
| `hr_admin` | `dashboard` | Sama dengan admin HR sesuai route aktif | `auth`, `role:super_admin,hr_admin` |
| `panitia` | `scanner.dashboard` | Scanner dan daftar attendance event | `auth`, `role:panitia` atau attendance group panitia |
| `pegawai` | `pegawai.dashboard` | Beranda, kegiatan, profil/wizard, dokumen sendiri, E-Card sendiri | `auth`, `role:pegawai` |

`UserRedirector` menentukan landing di server berdasarkan role. Intended URL hanya dipakai jika host aman, route membutuhkan auth, dan role middleware mengizinkan akses.

## 6. Route Baseline

- Total route aktif: 121.
- Tidak ditemukan duplicate route dengan kombinasi HTTP method + URI yang sama.
- Root `/` bernama `home`: guest diarahkan ke `login`; user login diarahkan melalui `UserRedirector`.
- `/login`: GET/POST dengan middleware `guest`.
- Register GET/POST masih terdaftar tetapi hanya redirect ke login dengan pesan bahwa registrasi harus memakai kode undangan; tidak membuat akun publik.
- Invitation registration tetap tersedia untuk guest melalui kode.
- Ringkasan area: Auth 17 hasil pencocokan, Dashboard 3, Employee/admin 17, Pegawai 34, master data 12, Event 17, Attendance/scanner 7, Report 6, Document 6, ID Card/QR 6, Import/Export route 5.
- Route file upload/download sensitif memakai auth/role dan dokumen memakai policy. Route `storage/{path}` dari Laravel local serving mensyaratkan signed relative URL untuk disk private.

## 7. Module Inventory

Status di bawah adalah kondisi **working tree saat audit**, bukan hanya HEAD. `PARTIAL` terutama berarti implementasi aktif masih berada dalam perubahan belum commit atau ada subfitur eksplisit belum tersedia.

| Modul | Route | Test | Status | Catatan |
|---|---|---|---|---|
| Authentication | login/logout/password/invitation | Auth suite | PARTIAL | Fungsi lulus test; login UI dan role redirect masih dirty/untracked |
| Dashboard | 3 dashboard role | DashboardAccess/Insights | PARTIAL | Insight controller/service/test belum tracked |
| Unit Kerja | resource institutions | MasterDataTest | STABLE | CRUD admin/HR tersedia |
| Jabatan | resource positions | MasterDataTest | STABLE | CRUD admin/HR tersedia |
| Data Pegawai | resource employees + NIK search | EmployeeManagement/Number | PARTIAL | Filter/UI/import dan controller masih dirty |
| Verifikasi Pegawai | verifications routes | EmployeeVerificationTest | STABLE | Approve/reject/document review dan QR generation teruji |
| Undangan Pegawai | invitation routes | EmployeeInvitationTest | STABLE | Generate/revoke/register-by-code tersedia |
| Profil Pegawai | 34 route area pegawai | Profile/Wizard/Submission tests | PARTIAL | Core teruji; UI mobile/profile masih dirty dan dua partial dihapus |
| Keluarga | profile family routes | EmployeeFamilyProfileTest | STABLE | CRUD ownership teruji |
| Pendidikan | profile education routes | EmployeeEducationCertificationTest | STABLE | Multi-record dan dokumen terkait tersedia |
| Sertifikasi | profile certification routes | EmployeeEducationCertificationTest | STABLE | Multi-record dan status efektif tersedia |
| Bank/Pajak/BPJS | administrative detail routes | EmployeeAdministrativeDetailTest | STABLE | One-to-one dan encrypted casts tersedia |
| Dokumen | pegawai CRUD + authorized access | DocumentSecurity/ProfileDocument | STABLE | Private storage + policy/controller response |
| NIK Security | exact search + Artisan commands | Feature + Unit NIK tests | STABLE | Encryption, HMAC lookup, masking, backfill/verify tersedia |
| QR | admin generate/regenerate + scanner | EmployeeQrCodeTest | STABLE | Random token, revocation, encrypted token storage |
| ID Card / E-Card | admin dan self-service routes | EmployeeECard/QR tests | PARTIAL | Browser E-Card aktif; component/test untracked; download PDF eksplisit belum tersedia |
| Kegiatan | resource + lifecycle | EventManagementTest | STABLE | Draft/active/closed/cancelled tersedia |
| Peserta | event participant routes | EventManagement/Attendance coverage | STABLE | Generate/manual/delete tersedia; tidak ada class test khusus bernama Participant |
| Scanner | event scanner + panitia dashboard | Attendance/QR tests | STABLE | QR-only scanner fisik, tanpa kamera browser |
| Attendance | list/scan/manual/delete | EventAttendanceHardeningTest | STABLE | Service terpusat dan duplicate race handling teruji |
| Laporan | employee/event/attendance | ReportConsistencyTest | STABLE | Filter dan label scan method tersedia |
| Export Excel | 3 report export routes | XlsxExportTest | STABLE | Custom `SimpleXlsxWriter` |
| Import Excel | employee import + template | EmployeeImportTest | PARTIAL | Aktif dan lulus test, tetapi seluruh implementation utama belum tracked |
| Employee mobile UI | dashboard/activity/E-Card/document/account | EmployeeSelfServiceUiTest | PARTIAL | App bar/bottom nav/activity/account files masih dirty/untracked |
| Login UI | `/login` | LoginEntryPoint/Auth tests | PARTIAL | Standalone Mantis split/mobile UI masih dirty |

## 8. Critical Business Rules

### NUP

- Source of truth aktif: `employees.employee_number`.
- `Employee::EMPLOYEE_NUMBER_LENGTH = 10`; helper memeriksa string, panjang 10, dan seluruh karakter digit.
- Kolom unique dan plaintext; tetap tampil pada profil/ID Card/laporan internal.
- `nup` dan `foundation_registry_number` masih ada sebagai kolom legacy migration tetapi tidak fillable dan tidak dipakai sebagai source aktif.
- Development aggregate: 0 row dengan legacy `nup`, 0 row dengan `foundation_registry_number`.

### Existing vs New Employee

- Seeder dan Employee Import: NUP tersedia/valid menghasilkan `verified`, metadata verifier, dan QR aktif; tanpa NUP menghasilkan `draft` tanpa QR.
- Employee admin form: `store()` selalu memaksa `verification_status = draft`, walaupun request dapat membawa NUP 10 digit.
- Development aggregate: 14 employee; 12 verified, 2 draft; terdapat 1 employee draft dengan NUP yang cocok pola 10 digit.
- Kesimpulan: keputusan "NUP valid = existing/verified" belum konsisten pada semua write path dan data aktual.

### NIK

- Interface model tetap `$employee->nik`, tetapi active value dibaca dari `nik_encrypted` dengan fallback legacy untuk transisi.
- Encryption memakai Laravel `Crypt`; exact lookup memakai HMAC SHA-256 dengan key terpisah; `nik_lookup` unique.
- `nik`, ciphertext, lookup, dan migration marker disembunyikan dari serialisasi; `masked_nik` tersedia.
- Exact admin search memakai `nik_lookup`, bukan LIKE/ciphertext.
- Development aggregate: 0 legacy plaintext NIK; 1 row memiliki pasangan ciphertext + lookup lengkap.
- Backfill dry-run/commit dan verify commands tersedia dan tidak dijalankan pada audit ini.

### QR

- Package: `chillerlan/php-qrcode` 5.0.0; output SVG.
- Payload: `YAPISTA:EMPLOYEE:{random 64-character token}`.
- Token tidak dibentuk dari NUP/NIK/ID/email. Database menyimpan SHA-256 token hash dan encrypted raw token; keduanya hidden.
- Generate menjaga satu token aktif; regenerate merevoke token lama; resolve mensyaratkan active dan belum revoked.
- Development aggregate: 12 token aktif.

### Profile dan Eligibility

- Profile review status terpisah: draft, submitted, approved, rejected.
- Profile completion hanya dapat diedit saat verification draft/rejected dan review draft/rejected.
- Eligibility event: verification `verified` dan status bukan nonaktif/resign; service participant juga menyaring NUP valid.
- Eligibility ID Card: verified + NUP valid + status bukan nonaktif/resign.
- Profile completion 100% tidak menjadi syarat ID Card, QR, participant, atau attendance.

### Attendance

- QR dan manual memakai `EventAttendanceService` yang sama.
- Scan baru memakai `qr`; manual memakai `manual`; `barcode` tetap label histori.
- Unique database event + employee tersedia.
- QR token aktif, event status, employee verified/NUP/status, participant active, dan duplicate divalidasi terpusat.

## 9. Test Baseline

- Command: `php artisan test`.
- Result: **PASSED**.
- Tests: 247.
- Passed: 247.
- Failed: 0.
- Skipped: 0.
- Assertions: 1.995.
- Duration: 27.482 ms (27,48 detik).
- Test files: 40 total (38 executable files plus base/support files according to inventory).

Coverage area tersedia untuk Auth, Dashboard, Master Data, Employee, NUP, NIK, Profile, Family, Education, Certification, Administration, Documents, QR, E-Card, Verification, Event, Attendance, Report, XLSX, Import, Seeder, filter, dan employee UI rendering.

Gap baseline:

- Tidak ada class test khusus Participant; behavior participant tercakup secara tidak langsung.
- Tidak ada automated browser/screenshot test lintas viewport.
- Tidak ada load/performance test.
- Security controls memiliki focused tests tertentu, tetapi audit penetrasi/deployment configuration belum dilakukan.

## 10. Frontend Build Baseline

- Command: `npm run build`.
- Result: **SUCCESS**.
- Vite: 8.0.10.
- Modules transformed: 56.
- Output:
  - `manifest.json`: 0,33 kB (gzip 0,16 kB)
  - CSS: 37,88 kB (gzip 7,27 kB)
  - JS: 86,49 kB (gzip 31,55 kB)
- Vite reported plugin timing warning: Laravel plugin 89%, CSS 10%; tidak ada unresolved asset atau build error.
- `git diff --check` setelah build tetap bersih.

## 11. Dependency Baseline

Composer direct dependencies penting:

- Laravel Framework 13.7.0; Breeze 2.4.1.
- QR: chillerlan/php-qrcode 5.0.0.
- Spreadsheet import: phpoffice/phpspreadsheet 5.9.0.
- PHPUnit 12.5.24; Faker 1.24.1; Mockery 1.6.12.
- Tinker 3.0.2; Pint 1.29.1; Collision 8.9.4; Pail 1.2.6; Pao 1.0.6.

npm direct dependencies:

- Vite 8.0.10; laravel-vite-plugin 3.1.0.
- Tailwind 3.4.19 plus `@tailwindcss/vite` 4.2.4 and forms 0.5.11.
- Alpine.js 3.15.12; Axios 1.16.0; PostCSS 8.5.14; Autoprefixer 10.5.0; Concurrently 9.2.1.
- `npm ls --depth=0` melaporkan lima package extraneous: `@emnapi/core`, `@emnapi/runtime`, `@emnapi/wasi-threads`, `@napi-rs/wasm-runtime`, `@tybys/wasm-util`.

Tidak ada install/update package pada audit ini.

## 12. UI/Layout Baseline

- Total Blade view: 102; view di area `pegawai`: 30.
- Main Mantis layout: `resources/views/layouts/admin.blade.php`; dipakai oleh 42 view.
- Employee desktop memakai layout Mantis yang sama; mobile memakai scoped `employee-app`, compact app bar, dan fixed bottom navigation lima item.
- Auth login adalah standalone Mantis page. Auth confirm/forgot/reset/verify masih memakai legacy Breeze guest layout; admin profile edit memakai legacy Breeze app layout.
- ID Card printable legacy view berdiri sendiri, tetapi route download sekarang hanya redirect dengan pesan bahwa PDF belum tersedia.
- Custom CSS `public/assets/css/yapista-ui.css`: 1.603 baris / 30.153 byte dan sedang modified; 77 selector occurrences employee, 50 auth, 38 dashboard. Tidak ditemukan override global langsung `.card`, `.row`, `body`, atau `.container-fluid` pada scan root selector.
- Ada 20 Blade files dengan inline `style=`; sebagian adalah width progress/dynamic presentation.
- Logo source utama: `public/assets/images/logo-yapista-hris.png` melalui `x-application-logo` dengan fallback text.
- Icon library lokal: Tabler (utama), Feather, Font Awesome, Material.
- Chart dashboard: ApexCharts 3.44.0 dari asset lokal.
- Semua literal `asset('...')` yang ditemukan tersedia secara fisik. Browser 404/console check belum dilakukan pada Stage 1.
- Google Public Sans dimuat dari Google Fonts pada layout admin/login sehingga ada dependency jaringan eksternal.

## 13. Seeder Baseline

- Seeder tidak dijalankan.
- `DatabaseSeeder` order: User, Institution, Position, Employee, Invitation, Document, Event, Participant, Attendance.
- `EmployeeQrTokenSeeder` tersedia tetapi tidak dipanggil oleh `DatabaseSeeder`; `EmployeeSeeder` sendiri menghasilkan/preserve QR untuk employee verified ber-NUP.
- Seeder memakai kombinasi transaction, `firstOrCreate`, dan `updateOrCreate`.
- Existing employee ber-NUP dipastikan verified; employee tanpa NUP draft; QR hanya untuk eligible verified employee.
- Event attendance demo baru memakai `qr` dan `manual`.
- Idempotency ditopang oleh `DatabaseSeederIdempotencyTest` dan `EmployeeOnboardingSeederTest`, keduanya lulus pada full suite.

## 14. Import/Export Baseline

| Area | Import | Template | Export | Authorization |
|---|---|---|---|---|
| Data Pegawai | Ada, tetapi implementation untracked | Ada XLSX | Ada XLSX | super_admin/hr_admin |
| Peserta | Tidak ada | Tidak ada | Tidak ada dedicated participant export | N/A |
| Reports Pegawai | N/A | N/A | Ada | super_admin/hr_admin |
| Reports Kegiatan | N/A | N/A | Ada | super_admin/hr_admin |
| Attendance | Tidak ada import | N/A | Ada per event | super_admin/hr_admin |
| Unit Kerja | Tidak ada | Tidak ada | Tidak ada | N/A |
| Jabatan | Tidak ada | Tidak ada | Tidak ada | N/A |

Employee import memakai PhpSpreadsheet dan satu definisi header: NUP, Nama Lengkap, Email Login, Email Pribadi, Unit Kerja, Jabatan, Jenis Pegawai, Status Kerja, Tanggal Masuk. File dibatasi XLSX/XLS/CSV dan 5 MB. NIK/KK/rekening/BPJS/pajak/password/QR tidak menjadi kolom import.

Report exports memakai custom `SimpleXlsxWriter`, bukan PhpSpreadsheet.

## 15. QR / E-Card Baseline

- Admin/HR dapat generate/regenerate QR; pegawai hanya melihat miliknya.
- Scanner menyelesaikan payload melalui token service dan meneruskan employee ke attendance service.
- E-Card web memakai component reusable `employee-e-card`, warna/layout digital, employee photo/name/position/institution/NUP, dan server-rendered QR SVG.
- Raw token tidak ditampilkan sebagai text/data attribute.
- Eligibility: verified, NUP 10 digit, employment status eligible, dan QR aktif.
- Photo fallback lokal tersedia.
- PDF/download belum tersedia; kedua download controller redirect kembali dengan pesan menggunakan browser print. `resources/views/id-cards/pdf.blade.php` masih ada sebagai standalone legacy view tetapi tidak dipakai controller download.

## 16. Security Controls Inventory

| Control | Status | Evidence ringkas |
|---|---|---|
| CSRF | ADA | Web middleware dan `@csrf` pada form mutating |
| Role middleware | ADA | `RoleMiddleware`, route groups empat role |
| Policy/ownership | ADA | `EmployeeDocumentPolicy`; employee self routes tidak menerima employee ID untuk E-Card |
| Private document storage | ADA | disk `private`, authorized controller view/download, no-store headers |
| NIK encryption | ADA | Laravel Crypt melalui dedicated service/model accessor |
| NIK HMAC blind index | ADA | SHA-256 HMAC dengan key terpisah dan exact lookup |
| Sensitive masking | ADA | Employee/family/admin/education/certification accessors |
| QR tokenization | ADA | Random 64-char, hash + encrypted token, revoke/regenerate |
| Login throttling | ADA | 5 attempts per email/IP key |
| Session regeneration | ADA | Login, logout, invitation register |
| File validation | ADA | Image/document/import request validation |
| Encrypted casts | ADA | KK, family NIK, bank, tax, BPJS, ijazah, sertifikat, QR raw token |
| Session secure cookie | BELUM DISET di local | Perlu environment deployment review |
| APP_DEBUG | TRUE di local | Harus false pada production; bukan diubah di Stage 1 |

Employee photo saat ini disimpan pada public disk dan disajikan melalui `public/storage`; berbeda dari employee documents yang private.

## 17. Debug / Artifact Audit

- Tidak ditemukan active `dd()`, `dump()`, `var_dump()`, `print_r()`, `console.log()`, TODO, FIXME, atau HACK di app/bootstrap/config/database/resources/routes/tests.
- Tidak ditemukan `.bak`, `.old`, `.tmp`, `debug*`, `test-output*`, atau `screenshot*` di source/public scope yang diaudit.
- Tidak ada literal local asset path yang missing pada scan statis.

## 18. Known Findings

| ID | Severity | Area | Finding | Evidence | Recommended Stage |
|---|---|---|---|---|---|
| FZ-001 | BLOCKER | Git/reproducibility | Baseline tidak dapat direproduksi dari HEAD karena 49 tracked changes dan 24 untracked files mencakup runtime code, dependency, UI, routes, dan tests. | `git status`, `git diff --stat` | Sebelum/awal Stage 2: review scope lalu simpan snapshot dalam commit/branch yang disetujui |
| FZ-002 | HIGH | Employee verification | Jalur admin selalu membuat employee draft walau NUP valid; importer/seeder membuat verified. Development juga memiliki 1 draft employee dengan NUP 10 digit. | `EmployeeController::store`, `EmployeeImportService`, read-only aggregate | Stage 2 dan Stage 4 |
| FZ-003 | HIGH | Date/time | Application timezone UTC, tetapi UI menampilkan beberapa jam sebagai WIB tanpa bukti konversi terpusat. Risiko waktu event/attendance bergeser. | `config/app.php`, `php artisan about`, attendance/activity views | Stage 2 |
| FZ-004 | MEDIUM | Personal data | Employee photos tersimpan di public disk dan dapat diakses melalui public storage link. Dokumen lain sudah private. | Employee/Profile/Wizard controllers, view photo URLs | Stage 3 |
| FZ-005 | MEDIUM | E-Card | Route download tersedia tetapi controller menyatakan PDF belum tersedia; legacy PDF view tidak terhubung. | kedua ID Card controllers, `id-cards/pdf.blade.php` | Stage 2 atau Stage 5 |
| FZ-006 | MEDIUM | Release configuration | APP_DEBUG aktif dan secure session cookie belum diset pada local baseline. Ini wajar untuk local, tetapi harus menjadi gate deployment. | `.env` flags dan session config | Stage 3 dan Stage 8 |
| FZ-007 | LOW | Dependencies | `npm ls` menemukan lima package extraneous. | `npm ls --depth=0` | Stage 6 |
| FZ-008 | LOW | Frontend build | Build sukses tetapi plugin timing warning menunjukkan 89% waktu build pada Laravel plugin. | `npm run build` | Stage 6 |
| FZ-009 | LOW | UI consistency | Mantis main layout hidup berdampingan dengan legacy Breeze app/guest layouts; 20 view masih memiliki inline style. | Blade inventory | Stage 5 |
| FZ-010 | LOW | Deployment assets | Public Sans memakai Google Fonts eksternal; tampilan dapat fallback bila jaringan eksternal dibatasi. | admin/login layout | Stage 8 |
| FZ-011 | INFO | Registration | Route register GET/POST tetap terdaftar untuk compatibility tetapi hanya redirect; public account creation tidak aktif. | `routes/auth.php` | Stage 3 verification |
| FZ-012 | INFO | Private local serving | Laravel mendaftarkan signed local storage GET/PUT routes; signature validation ada di framework handler. | `config/filesystems.php`, framework `ServeFile/ReceiveFile` | Stage 3 verification |
| FZ-013 | INFO | Quality | Full suite, build, diff check, static asset check, dan debug marker scan semuanya bersih. | Baseline commands | Keep as release evidence |

## 19. Feature Freeze Scope

Fitur berikut dibekukan dari penambahan fitur/redesign sampai finalization selesai:

- Authentication, role redirect, invitation onboarding.
- Dashboard admin/HR/panitia/pegawai.
- Unit kerja, jabatan, data pegawai, NUP, verification.
- Profile, family, education, certification, administration.
- Documents dan private access.
- NIK encryption/HMAC/masking.
- QR token lifecycle, scanner, E-Card.
- Event, participant, attendance.
- Reports, export, employee import.
- Employee mobile UI, login UI, filters, dan shared visual components.

Mulai Stage 2 hanya bug fix terverifikasi yang boleh masuk; tidak ada fitur baru.

## 20. Blocker untuk Tahap 2

1. **Working tree harus dipreservasi secara eksplisit** setelah owner mereview scope: saat ini implementasi penting hanya hidup sebagai perubahan lokal/untracked dan tidak terikat ke commit reproduktif.
2. Setelah snapshot disetujui, Stage 2 harus memprioritaskan mismatch NUP-valid versus verification status dan audit timezone UTC/WIB.
3. Tidak ada blocker test/build/migration: full suite hijau, build sukses, dan tidak ada migration pending.

Kesimpulan kesiapan: **secara runtime sistem dapat masuk Functional Bug Audit, tetapi secara release-control belum siap sampai dirty working tree dipreservasi dalam baseline commit/branch yang disetujui.** Tidak ada tindakan tersebut dilakukan pada Stage 1.

