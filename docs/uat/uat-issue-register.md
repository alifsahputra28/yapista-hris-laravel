# YAPISTA HRIS UAT Issue Register

Register ini diisi selama pilot internal. Jangan mencatat password, NIK, rekening, BPJS, QR token mentah, atau isi dokumen pribadi.

## Status Dan Severity

Status: `OPEN`, `IN PROGRESS`, `FIXED`, `RETEST PASS`, `DEFERRED POST-V1`, atau `NOT REPRODUCIBLE`.

Severity:

- `BLOCKER`: tugas utama tidak dapat dilanjutkan.
- `CRITICAL`: akses ilegal, data salah/hilang, identity mismatch, atau kebocoran keamanan.
- `HIGH`: fungsi operasional utama tidak dapat digunakan.
- `MEDIUM`: alur dapat dilanjutkan dengan hambatan signifikan.
- `LOW`: masalah minor/cosmetic.
- `SUGGESTION`: ide baru, bukan bug; pindahkan ke post-v1 backlog.

## Issue UAT

Belum ada issue UAT aktual karena human execution belum dimulai. Gunakan ID berurutan `UAT-001`, `UAT-002`, dan seterusnya.

| ID | Build | Role | Scenario | Severity | Issue | Expected | Actual | Fix | Retest | Status |
|---|---|---|---|---|---|---|---|---|---|---|
| _Belum ada_ |  |  |  |  |  |  |  |  |  |  |

## Release Gates Dari Tahap 8 Dan 9.5

Item berikut bukan hasil UAT baru, tetapi tetap memblokir Stage 10 sampai ditutup dan diverifikasi.

| Reference | Severity | Area | Kondisi aktual | Evidence/Action | Status |
|---|---|---|---|---|---|
| DEP-001 | BLOCKER | Composer | 30 advisory produksi pada 10 package | Targeted compatible update; audit 0; 297 tests PASS | FIXED |
| DEP-002 | BLOCKER | npm | 7 vulnerable packages | Targeted updates; audit penuh/production 0; build PASS | FIXED |
| DEP-003 | BLOCKER | Backup | Full MySQL dump dan isolated restore belum diuji | Restore drill PASS; counts, migration, NIK, dan QR verified | FIXED |
| DEP-004 | BLOCKER | Storage | 6 orphan legacy employee files berada di public storage | 6 quarantined; public remaining 0; permanent delete 0 | FIXED |
| DEP-005 | HIGH | Mail | Development memakai mail log; SMTP UAT/production belum diuji | Siapkan test mailbox/provider dan uji reset password | PENDING EXTERNAL |
| DEP-006 | HIGH | Infrastructure | Hosting, domain, TLS, monitoring, dan service account belum final | Lengkapi sebelum production deployment | PENDING EXTERNAL |
| DEP-007 | MEDIUM | Privacy/logging | Token undangan dapat masuk infrastructure access log | Terapkan redaction pada proxy/web log | PENDING EXTERNAL |
| DEP-008 | MEDIUM | Operations | RPO/RTO dan retention belum disetujui bisnis | Minta approval owner | PENDING EXTERNAL |
| DEP-009 | MEDIUM | Session | Secure cookie belum diuji pada HTTPS production-like environment | Set dan verifikasi di staging HTTPS | PENDING EXTERNAL |

## Workflow Perbaikan

1. Reproduce memakai scenario yang sama.
2. Tetapkan severity dan root cause.
3. Buat fix minimal dan regression test bila feasible.
4. Jalankan targeted test, full test, build, dan diff check.
5. Update UAT build/commit.
6. Tester asli mengulang scenario.
7. Tutup hanya sebagai `RETEST PASS`; status `FIXED` saja belum cukup.
