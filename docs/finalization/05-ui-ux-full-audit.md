# YAPISTA HRIS UI/UX Full Audit

Audit date: 2026-08-14 (Asia/Jakarta)

## 1. Baseline

- Branch: `main`
- HEAD: `3120bfbc0e90de9dc8006d856039c2ca8319c4bd` (`3120bfbc`)
- Working tree: sudah berisi perubahan Tahap 2-4 sebelum audit dimulai; seluruh perubahan tersebut dipertahankan.
- Migration: 26 Ran, 0 Pending berdasarkan baseline Tahap 4.
- Route aktif: 121.
- Blade view: 108 sesudah penambahan error presentation dan confirmation component.
- Active route/controller view references yang dipetakan: 48.
- Test awal Tahap 5: 269 passed, 2,152 assertions, 0 failed, 0 skipped.
- Build awal: PASS, Vite memproses 56 module.
- Test akhir Tahap 5: 273 passed, 2,172 assertions, 0 failed, 0 skipped.
- Build akhir: PASS, 56 module; CSS 49.62 kB (gzip 9.01 kB), JS 86.49 kB (gzip 31.55 kB).
- `git diff --check`: PASS. Peringatan konversi CRLF dari Git bukan whitespace error.

Audit ini mempertahankan business rule NUP, verification, QR, NIK security, attendance,
authorization, constraint database, dan private storage dari Tahap 1-4.

## 2. Active Layouts

| Area | Layout aktif | Hasil audit |
|---|---|---|
| Admin / HR / Panitia | `resources/views/layouts/admin.blade.php` | Mantis sidebar/header, flash area, dan content shell aktif dan konsisten. |
| Pegawai desktop | Layout admin yang diberi konteks role pegawai | Sidebar hanya berisi Beranda, Kegiatan, ID Card, Dokumen, dan Akun. |
| Pegawai mobile | Employee mobile shell di layout admin | Desktop sidebar/header disembunyikan, app bar dan fixed bottom navigation menjadi navigasi utama. |
| Authentication | `resources/views/layouts/guest.blade.php` | Split brand/form desktop dan compact brand/form mobile. |
| E-Card | View pegawai/admin memakai komponen e-card yang sama | Card dibatasi lebarnya di desktop dan tetap penuh secara proporsional di mobile. |
| Error | `resources/views/errors/layout.blade.php` | Standalone branded shell; tidak memuat sidebar admin. |
| Print | Layout/view print ID Card yang sudah ada | Tidak terkena override global Tahap 5. |

`resources/views/` dipetakan terhadap route/controller sebelum perubahan. View Breeze lama
yang tidak lagi menjadi presentation aktif tidak dihapus pada Tahap 5.

## 3. Design Consistency

- Visual hierarchy mengikuti Mantis dan Bootstrap: page header, card header/body, action
  group, status badge, form label, table wrapper, dan compact empty state.
- Brand green `#02936F` dipertahankan sebagai aksen, bukan dijadikan palette baru.
- Logo utama menggunakan satu asset lokal:
  `public/assets/images/logo-yapista-hris.png`, dirender melalui `<x-application-logo>`.
- Bahasa halaman aktif distandarkan ke Bahasa Indonesia. Label aktif seperti `Close`,
  `Cancel`, `Approve`, `Reject`, dan `Catatan Reject` telah diganti dengan istilah yang
  sesuai konteks.
- Mantis JavaScript sebelumnya menghapus atribut `lang` saat mode RTL dinonaktifkan.
  Layout sekarang mengembalikan `lang="id"` sesudah inisialisasi Mantis.
- Tanggal aktif menggunakan locale Indonesia melalui `translatedFormat`, sehingga contoh
  presentasi berubah dari `08 Aug 2026` menjadi `08 Agt 2026`.

## 4. Admin / HR

Halaman yang diperiksa meliputi dashboard, Data Pegawai list/detail/form, verifikasi,
undangan, Unit Kerja, Jabatan, kegiatan, peserta, attendance, dokumen, import Excel,
laporan pegawai, laporan kegiatan, dan laporan kehadiran.

- Dashboard mempertahankan KPI, chart, dan panel perhatian yang sudah tersedia.
- KPI mobile yang sebelumnya menjadi empat baris tinggi diubah menjadi grid 2 x 2.
- Data Pegawai mempertahankan search dominan, frequent filters, advanced filter, secure
  exact NIK lookup, active filter chips, dan pagination query.
- Detail pegawai memakai kelompok informasi dan card-body yang konsisten; data sensitif
  tetap masked.
- Form create/edit tetap mencerminkan dependensi Unit Kerja ke Jabatan.
- Import Excel tetap berupa modal Bootstrap, dengan input file, template download,
  hierarchy tombol sekunder/utama, dan layout mobile yang tidak overflow.
- Queue verifikasi membedakan status dan action. Copy action diseragamkan menjadi
  `Setujui`, `Tolak`, dan `Catatan Penolakan`.
- Unit/Jabatan tetap menggunakan tabel ringkas; destructive action sekarang memakai
  reusable confirmation modal.

## 5. Pegawai Desktop

- Desktop tetap memakai Mantis sidebar dan header, bukan mobile layout yang diperbesar.
- Menu dibatasi pada Beranda, Kegiatan, ID Card, Dokumen, dan Akun.
- Dashboard berisi informasi relevan: greeting, preview ID Card, kegiatan terdekat, dan
  kehadiran terakhir; tidak mengulang shortcut navigation.
- Halaman profile/account yang sebelumnya masih menggunakan Breeze/Tailwind diganti
  menjadi struktur Bootstrap/Mantis dan Bahasa Indonesia.
- Profile completion tidak dijadikan peringatan utama bagi pegawai existing yang sudah
  memiliki NUP valid.
- Kegiatan, dokumen, dan akun hanya menampilkan konteks milik pegawai login.

## 6. Pegawai Mobile

QA dilakukan pada home, kegiatan, ID Card, dokumen, akun/profile, dan wizard identitas.

- Pada 390/430 px, sidebar dan hamburger desktop tidak menjadi navigasi utama.
- Compact app bar dan fixed bottom navigation memiliki lima item lengkap dengan icon:
  Beranda, Kegiatan, ID Card, Dokumen, dan Akun.
- Icon Akun yang sebelumnya kosong diperbaiki dengan class Tabler yang tersedia.
- Main content memiliki bottom space yang cukup dan tidak tertutup bottom navigation.
- Home menggunakan section/card ringkas, tanpa shortcut grid atau duplicate identity card.
- Kegiatan memakai tabs/list employee-friendly, bukan tabel admin.
- Dokumen memakai list compact dengan status dan action.
- Akun memakai identity block tunggal dan settings-style list.
- Wizard pada mobile tetap satu kolom dan tidak menghasilkan overflow.

## 7. Panitia

- Dashboard fokus pada kegiatan aktif, peserta, scanner, dan attendance.
- Scanner tetap berupa physical QR scanner input; tidak ada browser camera UI.
- Scanner event summary pada 390 px diperbaiki menjadi KPI 2 x 2.
- Tabel event aktif sebelumnya terlalu terkompres pada 390 px. Tabel diberi scoped
  `min-width` dalam `.table-responsive`, sehingga kolom tetap terbaca dengan horizontal
  scroll yang disengaja dan tidak menyebabkan document overflow.
- Halaman scanner dan attendance mempertahankan feedback teks selain warna.
- Panitia tidak diperlihatkan menu HR yang tidak relevan.

## 8. Authentication

- Login desktop 1440/1024 px memakai split layout: panel brand hijau dan form dengan
  max-width yang nyaman.
- Login mobile 390/430 px memakai brand header ringkas dan form putih tanpa overflow.
- Logo lokal tampil dan tidak ada duplicate branding, link register publik, atau social login.
- Password toggle diuji: type berubah dari `password` ke `text` dan `aria-pressed` ikut
  diperbarui.
- Forgot password, reset password, confirm password, dan verify email yang sebelumnya
  masih berupa Breeze/Tailwind English sekarang mengikuti Mantis/Bootstrap dan Bahasa
  Indonesia.
- Root/login redirect dan authentication business logic tidak diubah.

## 9. Forms

- Form aktif menggunakan label/accessibility name, `form-control`/`form-select`,
  `is-invalid`, dan `invalid-feedback` sesuai Bootstrap.
- Auth recovery dan account form yang sebelumnya tidak sekeluarga dengan aplikasi telah
  distandarkan.
- Required marker dan helper tetap mengikuti konteks form; tidak dibuat CSS global baru.
- File input import tetap full width pada mobile.
- Photo presentation menggunakan class `object-fit-cover`; duplicate inline
  `object-fit: cover` pada view pegawai/verifikasi dihapus.

## 10. Tables

- Semua tabel aktif yang diaudit berada dalam `.table-responsive`.
- Automated DOM audit tidak menemukan active table tanpa responsive wrapper.
- Admin table tetap menggunakan horizontal responsive strategy bila kolom administratif
  tidak dapat dipadatkan dengan aman.
- Employee self-service menggunakan list/card pada mobile.
- Panitia active-event table mendapat scoped minimum width untuk mencegah cell menjadi
  terlalu sempit.
- Empty table state tetap compact dan berada dalam konteks tabel/card.

## 11. Filters

- Data Pegawai dan reports mempertahankan hierarchy search, frequent filter, advanced
  filter, chips, reset, dan pagination query yang sudah stabil.
- Secure exact NIK lookup tidak digabungkan ke general search dan tidak diubah menjadi
  query-string plaintext.
- Filter tidak diduplikasi dan tidak ada backend filter logic yang diubah.
- Responsive QA menunjukkan controls wrap tanpa document overflow pada seluruh breakpoint.

## 12. Modals

- Dibuat `resources/views/components/confirm-action-modal.blade.php` sebagai modal
  konfirmasi reusable.
- Sekitar 20 penggunaan browser `confirm()` pada view aktif diganti dengan atribut
  `data-confirm-title` dan `data-confirm-message`.
- Modal menangani submit setelah konfirmasi tanpa mengubah route, method, CSRF, atau
  authorization.
- Confirmation modal diuji pada Unit Kerja 390 px: title, message, Batal, dan action utama
  tampil lengkap tanpa overflow.
- Modal delete account pada profile nonemployee juga mengikuti Bootstrap.

## 13. Import / Export

- Employee import modal dirender normal pada desktop dan 390 px.
- Icon spreadsheet tersedia, file input memiliki accessible label, template download
  terlihat, dan footer action wrap dengan benar.
- Import/export backend, template columns, file validation, dan authorization tidak diubah.
- Reports/export action tetap mengikuti filter existing.
- Tidak ada asset eksternal atau package UI baru.

## 14. E-Card

- E-Card menggunakan data dinamis employee, protected photo endpoint/fallback, NUP
  plaintext, dan QR token renderer existing.
- Layout green digital card tampil proporsional pada 390, 430, 768, 1024, dan 1440 px.
- Foto tidak stretch, nama/unit dapat wrap, QR tidak tertutup, dan card tidak stretch penuh
  di desktop.
- Raw QR token dan NIK tidak tampil pada HTML presentation.
- Unavailable state existing dipertahankan.
- Download PDF masih placeholder/disabled sesuai baseline dan tidak diimplementasikan,
  karena merupakan fitur baru di luar Tahap 5.

## 15. Responsive QA

Automated checks pada setiap cell meliputi document overflow, broken image, table wrapper,
duplicate DOM id, accessible form control name, dan document language. Intentional table
scroll di dalam `.table-responsive` tidak dihitung sebagai document overflow.

| Page | 390 | 430 | 768 | 1024 | 1440 |
|---|---:|---:|---:|---:|---:|
| Login | PASS | PASS | PASS | PASS | PASS |
| Forgot Password | PASS | PASS | PASS | PASS | PASS |
| Admin Dashboard | PASS | PASS | PASS | PASS | PASS |
| Data Pegawai | PASS | PASS | PASS | PASS | PASS |
| Laporan Pegawai | PASS | PASS | PASS | PASS | PASS |
| Pegawai Beranda | PASS | PASS | PASS | PASS | PASS |
| Pegawai Kegiatan | PASS | PASS | PASS | PASS | PASS |
| Pegawai E-Card | PASS | PASS | PASS | PASS | PASS |
| Pegawai Dokumen | PASS | PASS | PASS | PASS | PASS |
| Pegawai Akun/Profile | PASS | PASS | PASS | PASS | PASS |
| Scanner Dashboard | PASS | PASS | PASS | PASS | PASS |
| Event Scanner | PASS | PASS | PASS | PASS | PASS |
| Attendance | PASS | PASS | PASS | PASS | PASS |

Additional 390 px manual/visual checks: employee detail, import modal, confirmation modal,
wizard identitas, error 404, long E-Card content, dan password toggle.

## 16. Accessibility

- Automated audit pada halaman kritis menghasilkan 0 form control tanpa label atau
  accessible name.
- Duplicate DOM ID: 0 pada seluruh halaman/breakpoint yang diaudit.
- Icon-only close/toggle/remove controls memakai label/title/Bootstrap semantics.
- Bottom navigation menggunakan link, icon, dan visible label; active state tidak hanya
  disampaikan oleh warna.
- Confirmation dan auth recovery memakai native Bootstrap modal/form semantics.
- Password toggle memperbarui `aria-pressed`.
- HTML language aktif adalah `id` sesudah Mantis initialization.
- Tidak ditemukan custom `outline: none` yang menghapus focus tanpa alternatif.

## 17. Asset / Console Audit

- Broken image pada automated browser matrix: 0.
- JavaScript console error/warning yang tercatat selama QA: 0.
- Asset 404 pada halaman kritis: 0.
- Logo source final: `public/assets/images/logo-yapista-hris.png`.
- Icon library: Tabler Icons font bundled oleh Mantis.
- Audit awal menemukan 12 class icon yang tidak tersedia pada font aktif:
  `ti-circle-check-filled`, `ti-clipboard-off`, `ti-clock-exclamation`,
  `ti-file-description`, `ti-file-spreadsheet`, `ti-id-off`, `ti-mail-check`,
  `ti-mail-off`, `ti-mail-plus`, `ti-user-circle`, `ti-user-question`, dan
  `ti-users-off`.
- Semua class tersebut diganti dengan equivalent yang memang tersedia. Pencarian akhir
  terhadap source vs font menghasilkan 0 class Tabler invalid.

## 18. UI Findings

| ID | Severity | Page | Viewport | Finding | Fix | Status |
|---|---|---|---|---|---|---|
| UI-001 | MEDIUM | Auth recovery dan account | Semua | Breeze/Tailwind English tidak konsisten dengan Mantis | Rebuild presentation dengan guest/admin Mantis + Bootstrap, tanpa mengubah auth logic | FIXED |
| UI-002 | MEDIUM | Navigation, status, empty states | Semua | 12 class Tabler tidak tersedia sehingga icon dapat kosong | Ganti dengan equivalent icon font yang tersedia dan audit ulang source | FIXED |
| UI-003 | MEDIUM | Admin dashboard dan scanner summary | 390/430 | Empat KPI menjadi empat baris tinggi | Gunakan grid 2 x 2 pada mobile | FIXED |
| UI-004 | MEDIUM | Destructive actions | Semua | Sekitar 20 browser `confirm()` tidak konsisten dan sulit distandarkan | Buat reusable Bootstrap confirmation modal | FIXED |
| UI-005 | LOW | Dates | Semua | Bulan tampil dalam singkatan English | Gunakan Carbon locale Indonesia dan `translatedFormat` | FIXED |
| UI-006 | MEDIUM | 403/404/419/500 | Semua | Tidak ada branded presentation yang konsisten | Tambah standalone error layout dan empat view error | FIXED |
| UI-007 | LOW | Semua Mantis pages | Semua | Mantis script menghapus `html[lang]` pada mode non-RTL | Restore `lang=id` setelah inisialisasi Mantis | FIXED |
| UI-008 | LOW | Panitia dashboard | 390 | Kolom tabel event aktif terlalu terkompres | Scoped min-width dalam responsive wrapper | FIXED |
| UI-009 | LOW | Event dan verification | Semua | Leftover action copy English | Ubah ke Bahasa Indonesia yang sesuai konteks | FIXED |
| UI-010 | LOW | Legacy Breeze partial/layout | N/A | View tidak aktif masih memakai Tailwind/English | Tidak disentuh untuk menghindari cleanup di luar route aktif | DEFER STAGE 6 |
| UI-011 | INFO | E-Card | Semua | Download PDF masih placeholder/disabled | Tidak diimplementasikan karena fitur baru | DEFER PRODUCT DECISION |

Tidak ditemukan finding HIGH setelah browser reproduction. Lima finding MEDIUM ditemukan
dan seluruhnya diperbaiki. Empat finding LOW diperbaiki dan satu legacy cleanup didefer.

## 19. Fixes

### Components and layouts

- Menambahkan reusable confirmation modal.
- Menambahkan branded error layout dan view 403/404/419/500.
- Menstandarkan auth recovery dan account profile ke Mantis/Bootstrap.
- Memulihkan document language setelah Mantis runtime initialization.

### Presentation

- Mengganti seluruh class icon aktif yang tidak ada pada bundled font.
- Melokalkan 31 date rendering call pada 18 view aktif.
- Mengganti action copy English yang aktif.
- Mengubah KPI mobile menjadi 2 x 2.
- Menjaga tabel panitia terbaca melalui responsive container.
- Menghapus duplicate inline photo object-fit declarations.

### Regression protection

- Menambahkan `tests/Feature/UiPresentationTest.php` untuk error presentation,
  confirmation modal, active icon source, dan language semantics.
- Menyesuaikan `tests/Feature/Auth/PasswordResetTest.php` terhadap presentation baru.
- Targeted UI test: 4 passed, 18 assertions.
- Targeted password reset test: 4 passed, 10 assertions.
- Broad auth/profile regression: 66 passed, 736 assertions.
- `php artisan view:cache`: PASS.

### Custom CSS

- Perubahan dibatasi pada `public/assets/css/yapista-ui.css`.
- Tambahan hanya untuk metric grid mobile, scoped scanner event table, dan presentation
  component yang sudah ada.
- Tidak ada override global baru terhadap `.card`, `.btn`, `.form-control`, `table`,
  `img`, `.navbar`, atau `.container-fluid`.
- Tidak digunakan `body { overflow-x: hidden; }` untuk menyembunyikan layout bug.

## 20. Remaining Issues

1. View/partial Breeze legacy yang tidak mempunyai route presentation aktif masih memuat
   Tailwind/English. Cleanup dan dead-view classification didefer ke Tahap 6 Performance &
   Code Quality; file tidak dihapus pada Tahap 5.
2. Download PDF E-Card tetap placeholder/disabled. Ini fitur baru dan membutuhkan keputusan
   produk terpisah, bukan UI audit.
3. QA browser mencakup matriks halaman kritis dan route representatif, bukan setiap kombinasi
   data/permission. Regression/edge-case breadth dilanjutkan pada Tahap 7.

Tidak ada blocker UI yang tersisa untuk masuk Tahap 6. Tahap 5 berhenti di sini dan tidak
menjalankan pekerjaan Performance & Code Quality.
