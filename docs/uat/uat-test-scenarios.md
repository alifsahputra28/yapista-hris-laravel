# YAPISTA HRIS UAT Test Scenarios

Lembar ini digunakan langsung oleh tester internal. Gunakan data dummy/masked dan jangan menulis password atau data pribadi pada catatan/evidence.

## Cara Mengisi

Pilih satu status untuk setiap scenario: `PASS`, `PASS WITH NOTE`, `FAIL`, `BLOCKED`, atau `NOT APPLICABLE`. Isi Actual Result dan Notes setelah tugas dilakukan. Kesulitan memahami alur tetap dicatat walaupun fungsi akhirnya berhasil.

Naming data dummy yang disarankan: `UAT Employee 001`, `UAT Kegiatan Internal 001`, dan NUP dummy 10 digit yang belum dipakai. Event memakai 5-10 participant dummy. Gunakan file dummy tanpa NIK, rekening, BPJS, atau dokumen asli.

## Priority Matrix

Priority adalah source of truth untuk urutan human execution:

- `P0 RELEASE CRITICAL`: wajib dieksekusi dan PASS sebelum sign-off.
- `P1 CORE`: core usability/operations; failure ditriage sebelum release.
- `P2 SUPPORTING`: presentation/supporting behavior, tetap dicatat.

| Priority | Count | Scenario IDs |
|---|---:|---|
| P0 | 31 | AUTH-001, AUTH-003-007; ADM-004-005; HR-002, HR-005; EMP-004, EMP-006, EMP-008; NEW-004-006; PAN-002-004, PAN-006-008, PAN-010; IMP-001-004; REP-001-002; SEC-001-002 |
| P1 | 22 | AUTH-002; ADM-002-003, ADM-006-008; HR-001, HR-003-004, HR-006; EMP-001, EMP-003, EMP-005, EMP-007, EMP-009; NEW-001-003; PAN-001, PAN-005, PAN-009; REP-003 |
| P2 | 2 | ADM-001; EMP-002 |

Prefix lengkap seluruh ID adalah `UAT-`. Matriks mencakup seluruh 55 scenario tepat satu kali.

P0 mencakup authentication/role landing, existing employee dan E-Card, new employee verification, event/participant, physical QR scan, duplicate/revoked/non-participant handling, manual attendance, import, report/export consistency, authorization, document privacy, serta password/account.

## UAT Data And Device Setup

Checklist operator sebelum human execution:

- Provision `UAT-SA-01`, `UAT-HR-01`, `UAT-EMP-EX-01`, `UAT-EMP-NEW-01`, dan `UAT-PAN-01` di environment UAT; password dibagikan melalui channel rahasia dan tidak disimpan di Git.
- `UAT-EMP-EX-01`: NUP valid 10 digit, verified, employment active, satu QR aktif, dan profile boleh belum lengkap.
- `UAT-EMP-NEW-01`: NUP null, belum verified, dan tidak mempunyai QR aktif.
- `UAT-PAN-01`: role `panitia`, tanpa akses HR/admin.
- Buat `UAT Kegiatan Internal 001` dan 5-10 participant dummy.
- Siapkan scanner QR/2D USB HID. Catat tipe perangkat, focus behavior, dan rapid scan result tanpa serial number.
- Siapkan inbox SMTP test bila external mail delivery termasuk scope pilot.
- Jangan gunakan employee atau dokumen pribadi real kecuali ada approval dan kebutuhan eksplisit.

## Account Matrix

| UAT ID | Role | Scenario | Environment | Status |
|---|---|---|---|---|
| UAT-SA-01 | Super Admin | Administrasi penuh, master data, dashboard | Staging/internal pilot | NOT PROVISIONED |
| UAT-HR-01 | HR Admin | Pegawai, verifikasi, import/export | Staging/internal pilot | NOT PROVISIONED |
| UAT-EMP-EX-01 | Pegawai Existing | NUP valid, verified, profile boleh belum lengkap | Mobile + desktop | NOT PROVISIONED |
| UAT-EMP-NEW-01 | Pegawai Baru | Tanpa NUP, onboarding dan verifikasi | Mobile + desktop | NOT PROVISIONED |
| UAT-PAN-01 | Panitia | Scanner dan kehadiran | Desktop/scanner station | NOT PROVISIONED |

## Scenario Inventory

Kolom Result/Status/Notes sengaja kosong sampai diisi tester manusia.

### Authentication

| ID | Role | Precondition | Task | Expected Result | Actual Result | Status | Notes |
|---|---|---|---|---|---|---|---|
| UAT-AUTH-001 | Guest | Belum login, desktop 1366/1440 px | Buka aplikasi dan login dengan credential salah | Root menuju login; error mudah dipahami; tidak ada detail teknis |  |  |  |
| UAT-AUTH-002 | Guest | Mobile 390 px | Buka login, gunakan password toggle, periksa form | Form nyaman, tidak overflow, tombol masuk terlihat |  |  |  |
| UAT-AUTH-003 | Super Admin | Account aktif | Login | Masuk Dashboard admin, menu sesuai role |  |  |  |
| UAT-AUTH-004 | HR Admin | Account aktif | Login | Masuk Dashboard admin/HR, menu HR tersedia |  |  |  |
| UAT-AUTH-005 | Existing Employee | NUP valid, verified | Login | Masuk Beranda Pegawai tanpa forced onboarding |  |  |  |
| UAT-AUTH-006 | New Employee | Account aktif tanpa NUP | Login | Masuk area Pegawai dan dapat membuka profile/wizard |  |  |  |
| UAT-AUTH-007 | Panitia | Account aktif | Login | Masuk dashboard scanner; tidak melihat menu HR sensitif |  |  |  |

### Super Admin

| ID | Role | Precondition | Task | Expected Result | Actual Result | Status | Notes |
|---|---|---|---|---|---|---|---|
| UAT-ADM-001 | Super Admin | Login | Pahami KPI, chart, dan panel perhatian tanpa bantuan teknis | Informasi utama dan action dapat dipahami |  |  |  |
| UAT-ADM-002 | Super Admin | Ada data dummy | Cari pegawai; filter unit, jabatan, status; reset | Hasil relevan, filter aktif jelas, reset bekerja |  |  |  |
| UAT-ADM-003 | Super Admin | Ada employee dummy | Buka detail employee | Data utama, status, dokumen, dan action terbaca jelas |  |  |  |
| UAT-ADM-004 | Super Admin | NUP dummy 10 digit belum dipakai | Tambah existing employee dengan NUP | Employee verified, QR aktif, E-Card tersedia tanpa profile 100% |  |  |  |
| UAT-ADM-005 | Super Admin | Email dummy belum dipakai | Tambah employee tanpa NUP | Status draft/workflow baru, QR dan ID Card valid belum tersedia |  |  |  |
| UAT-ADM-006 | Super Admin | Nama unit dummy | Tambah/edit unit, coba duplicate, hapus unit unused | Create/edit normal; duplicate ditolak; delete aman |  |  |  |
| UAT-ADM-007 | Super Admin | Dua unit dummy | Tambah jabatan; duplicate unit sama; nama sama unit lain | Duplicate dalam unit sama ditolak; lintas unit diizinkan |  |  |  |
| UAT-ADM-008 | Super Admin | Employee eligible | Buka E-Card preview | Foto, nama, jabatan, unit, NUP, status, dan QR benar |  |  |  |

### HR Admin

| ID | Role | Precondition | Task | Expected Result | Actual Result | Status | Notes |
|---|---|---|---|---|---|---|---|
| UAT-HR-001 | HR Admin | Data employee dummy | Cari, buka detail, edit informasi kepegawaian | Flow intuitif; NUP/status/read-only context jelas |  |  |  |
| UAT-HR-002 | HR Admin | Employee submitted/draft eligible | Review dan approve | Status verified, metadata verifier benar, NUP/QR sesuai flow |  |  |  |
| UAT-HR-003 | HR Admin | Employee sudah verified | Refresh/kembali ke halaman verifikasi | Tidak ada approve ganda atau action membingungkan |  |  |  |
| UAT-HR-004 | HR Admin | Dummy employee dapat ditolak | Reject, lihat status, employee koreksi dan submit ulang | Alasan/status jelas; resubmit dapat direview |  |  |  |
| UAT-HR-005 | HR Admin | Ada dummy document | Review status dokumen dan coba preview/download | Dokumen benar, feedback sukses jelas, akses aman |  |  |  |
| UAT-HR-006 | HR Admin | Ada filter employee | Export hasil filter dan buka file | File terbuka, header jelas, filter diterapkan, tidak ada data sensitif tak perlu |  |  |  |

### Existing Employee

| ID | Role | Precondition | Task | Expected Result | Actual Result | Status | Notes |
|---|---|---|---|---|---|---|---|
| UAT-EMP-001 | Existing Employee | Mobile 390 px | Login dan lihat Beranda | App bar/bottom nav nyaman; E-Card, kegiatan, kehadiran mudah ditemukan |  |  |  |
| UAT-EMP-002 | Existing Employee | Mobile 430 px | Ulangi Beranda dan scroll | Tidak overflow/tertutup; spacing nyaman |  |  |  |
| UAT-EMP-003 | Existing Employee | Mobile | Buka Beranda, Kegiatan, ID Card, Dokumen, Akun via bottom nav | Semua route dan active state benar; hamburger tidak diperlukan |  |  |  |
| UAT-EMP-004 | Existing Employee | NUP dan QR aktif | Buka ID Card pada mobile lalu desktop | Foto/nama/jabatan/unit/NUP/status/QR benar; raw token tidak terlihat |  |  |  |
| UAT-EMP-005 | Existing Employee | Ada upcoming/history dummy | Buka Kegiatan, tab upcoming/history, lalu detail | Nama, tanggal, waktu, lokasi, dan status mudah dipahami |  |  |  |
| UAT-EMP-006 | Existing Employee | Dummy file aman | Buka Dokumen, upload, preview/download, dan periksa ownership | Hanya dokumen sendiri terlihat; sukses/error jelas |  |  |  |
| UAT-EMP-007 | Existing Employee | Login | Buka Akun, Data Pribadi, Informasi Kepegawaian, Keamanan | Menu dipahami; informasi read-only tidak berulang berlebihan |  |  |  |
| UAT-EMP-008 | Existing Employee | Account dummy | Ubah password lalu login ulang | Validasi jelas; password baru bekerja; nilai tidak dicatat |  |  |  |
| UAT-EMP-009 | Existing Employee | Profile belum lengkap, NUP valid | Buka Beranda dan Akun | Tidak ada warning bahwa akun tidak resmi; completion bersifat tambahan |  |  |  |

### New Employee

| ID | Role | Precondition | Task | Expected Result | Actual Result | Status | Notes |
|---|---|---|---|---|---|---|---|
| UAT-NEW-001 | New Employee | Account invitation tanpa NUP | First login dan buka profile | Pengguna memahami tindakan berikutnya |  |  |  |
| UAT-NEW-002 | New Employee | Mobile 390/430 px | Jalankan enam langkah wizard; Next/Kembali | Step jelas, navigation bekerja, tidak ada progress duplicate |  |  |  |
| UAT-NEW-003 | New Employee | Wizard terisi sebagian | Simpan, logout, login kembali | Draft tersimpan sesuai design |  |  |  |
| UAT-NEW-004 | New Employee | Data wajib dummy lengkap | Review dan submit profile | Status/feedback submit jelas dan tidak duplicate saat refresh/back |  |  |  |
| UAT-NEW-005 | New Employee + HR | Profile submitted | HR review, approve, dan berikan NUP sesuai flow | Verified, NUP valid, QR aktif, identity tetap employee yang sama |  |  |  |
| UAT-NEW-006 | New Employee | Skenario sebelumnya approved | Login ulang dan buka E-Card | E-Card tersedia dengan data dan QR yang benar |  |  |  |

### Panitia And Attendance

| ID | Role | Precondition | Task | Expected Result | Actual Result | Status | Notes |
|---|---|---|---|---|---|---|---|
| UAT-PAN-001 | Panitia | Account aktif | Login dan periksa menu | Dashboard scanner tersedia; area HR tidak terlihat |  |  |  |
| UAT-PAN-002 | Panitia | UAT Event aktif | Pilih event dan lihat peserta | Konteks event/peserta jelas sebelum scan |  |  |  |
| UAT-PAN-003 | Panitia | QR aktif participant terdaftar | Scan melalui USB QR scanner keyboard HID | Success cepat dan informasi peserta cukup |  |  |  |
| UAT-PAN-004 | Panitia | UAT-PAN-003 selesai | Scan QR yang sama lagi | Pesan sudah hadir; attendance kedua tidak dibuat |  |  |  |
| UAT-PAN-005 | Panitia | Event aktif | Scan input random/tidak dikenal | Pesan QR tidak valid tanpa error teknis |  |  |  |
| UAT-PAN-006 | Panitia | QR dummy diregenerate | Scan QR lama | QR lama ditolak sebagai tidak aktif/revoked |  |  |  |
| UAT-PAN-007 | Panitia | QR aktif employee non-participant | Scan | Pesan tidak terdaftar sebagai peserta |  |  |  |
| UAT-PAN-008 | Panitia | Participant belum hadir | Catat attendance manual | Berhasil dan metode tampil Manual, bukan QR |  |  |  |
| UAT-PAN-009 | Panitia | Attendance dummy tersedia | Buka daftar hadir, search/filter | Scan/manual terlihat dan hasil filter benar |  |  |  |
| UAT-PAN-010 | Panitia | 5-10 QR dummy | Scan berurutan dengan Enter suffix | Fokus kembali otomatis, tidak perlu reset manual, hasil nyaman dibaca |  |  |  |

### Import Excel

| ID | Role | Precondition | Task | Expected Result | Actual Result | Status | Notes |
|---|---|---|---|---|---|---|---|
| UAT-IMP-001 | HR/Admin | Buka Data Pegawai | Buka modal import dan download template | Tombol/modal jelas; file template dapat dibuka dan header dipahami |  |  |  |
| UAT-IMP-002 | HR/Admin | Template dengan 3-10 row dummy valid | Upload dan import | Summary berhasil/dilewati/gagal jelas; data sesuai |  |  |  |
| UAT-IMP-003 | HR/Admin | File dengan invalid NUP/unit/jabatan | Import file invalid | Error menunjuk baris/masalah secara ramah tanpa SQL/path/class |  |  |  |
| UAT-IMP-004 | HR/Admin | Row existing NUP dan row tanpa NUP | Import lalu buka hasil | NUP valid menjadi verified+QR; tanpa NUP tetap workflow draft |  |  |  |

### Reports And Pilot Consistency

| ID | Role | Precondition | Task | Expected Result | Actual Result | Status | Notes |
|---|---|---|---|---|---|---|---|
| UAT-REP-001 | HR/Admin | UAT Event sudah memiliki attendance | Filter report dan export | Summary/filter/export dipahami dan file terbuka |  |  |  |
| UAT-REP-002 | HR/Admin | Count pilot dicatat | Bandingkan participant/hadir di event, attendance, dan report | Jumlah konsisten; duplicate tidak menambah hadir |  |  |  |
| UAT-REP-003 | HR/Admin | Ada histori legacy barcode bila tersedia | Buka report/history | Histori tetap terbaca sebagai Barcode Lama; tidak membuat scan barcode baru |  |  |  |

### Authorization And Privacy

| ID | Role | Precondition | Task | Expected Result | Actual Result | Status | Notes |
|---|---|---|---|---|---|---|---|
| UAT-SEC-001 | Pegawai A | Dua employee dummy | Gunakan UI normal dan periksa menu dokumen | Tidak ada menu/link untuk dokumen Pegawai B |  |  |  |
| UAT-SEC-002 | Panitia | Login | Jelajahi menu yang tersedia | Tidak ada NIK, bank, BPJS, profile HR, import, atau report sensitif |  |  |  |

Total scenario yang disiapkan: **55**. Human executed: **0** pada saat dokumen dibuat.

## Critical Task Sheets

### UAT-ADM-004 - Tambah Pegawai Existing

Precondition:

- Login sebagai `UAT-SA-01`.
- Siapkan NUP dummy tepat 10 digit yang belum dipakai.

Steps:

1. Buka Data Pegawai dan pilih Tambah Pegawai.
2. Isi data minimum dengan NUP dummy.
3. Simpan dan buka detail.
4. Buka E-Card.

Expected:

- Status verified.
- QR aktif dan E-Card tersedia.
- Profile 100% tidak menjadi syarat.

Result: [ ] PASS [ ] PASS WITH NOTE [ ] FAIL [ ] BLOCKED [ ] NOT APPLICABLE

Notes/Evidence:

### UAT-NEW-005 - Verifikasi End-to-End

Precondition: `UAT-EMP-NEW-01` sudah submit profile dummy.

Steps:

1. HR membuka antrean verifikasi.
2. Review data tanpa menyalin data sensitif ke evidence.
3. Approve dan tetapkan NUP sesuai flow aktual.
4. Employee login ulang dan membuka E-Card.

Expected: identity sama, verified, NUP valid, QR aktif, E-Card tersedia.

Result: [ ] PASS [ ] PASS WITH NOTE [ ] FAIL [ ] BLOCKED [ ] NOT APPLICABLE

Notes/Evidence:

### UAT-PAN-003/004 - QR Dan Duplicate

Precondition: UAT Event aktif, participant dummy terdaftar, USB QR scanner keyboard HID tersedia.

Steps:

1. Scan QR aktif.
2. Tunggu feedback.
3. Scan QR sama sekali lagi.
4. Periksa daftar hadir.

Expected: scan pertama success; scan kedua sudah hadir; hanya satu attendance.

Result: [ ] PASS [ ] PASS WITH NOTE [ ] FAIL [ ] BLOCKED [ ] NOT APPLICABLE

Device note (generic only):

Notes/Evidence:

### UAT-IMP-001/002/003 - Import Excel

Precondition: login HR/Admin, 3-10 row dummy, satu file valid dan satu file sengaja invalid.

Steps:

1. Download template dari modal.
2. Isi dan import file valid.
3. Catat summary tanpa menyalin data sensitif.
4. Import file invalid.

Expected: template dipahami, valid import berhasil, error invalid mudah dipahami.

Result: [ ] PASS [ ] PASS WITH NOTE [ ] FAIL [ ] BLOCKED [ ] NOT APPLICABLE

Notes/Evidence:

### UAT-REP-002 - Validasi Angka Pilot

Precondition: `UAT Event 001` memiliki 5-10 participant dummy dan kombinasi QR/manual attendance.

Record expected aggregate only:

| Metric | Expected | Actual |
|---|---:|---:|
| Participants |  |  |
| QR attendance |  |  |
| Manual attendance |  |  |
| Total attendance |  |  |
| Duplicate attendance | 0 |  |

Result: [ ] PASS [ ] PASS WITH NOTE [ ] FAIL [ ] BLOCKED [ ] NOT APPLICABLE

## Responsive Matrix

| Screen | 390 px | 430 px | 768 px | 1024 px | 1440 px | Tester Result/Notes |
|---|---|---|---|---|---|---|
| Login |  |  |  |  |  |  |
| Beranda Pegawai |  |  |  |  |  |  |
| Kegiatan |  |  |  |  |  |  |
| E-Card |  |  |  |  |  |  |
| Dokumen |  |  |  |  |  |  |
| Akun/Profile |  |  |  |  |  |  |
| Admin Dashboard | N/A | N/A |  |  |  |  |
| Data Pegawai | N/A | N/A |  |  |  |  |
| Scanner |  |  |  |  |  |  |

## Browser Matrix

Isi hanya browser/device yang benar-benar diuji.

| Browser/Device | Version/Generic Device | Areas | Result | Notes |
|---|---|---|---|---|
| Chrome/Edge desktop |  |  |  |  |
| Chrome Android |  |  |  |  |
| USB QR scanner - keyboard HID |  | Scanner |  |  |

Jangan menulis Safari/iPhone atau browser lain sebagai PASS tanpa eksekusi nyata.
