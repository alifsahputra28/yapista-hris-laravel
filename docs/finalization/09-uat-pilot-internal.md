# YAPISTA HRIS UAT / Pilot Internal

## 1. Baseline

Tahap 9 dimulai sebagai persiapan UAT, bukan klaim acceptance. Empat technical blockers dari Stage 8 telah ditutup pada Tahap 9.5. Human UAT dan human sign-off tetap belum tersedia dan tidak dapat digantikan automated test atau browser smoke.

Baseline aktual:

| Item | Result |
|---|---|
| Branch | `main` |
| Initial HEAD | `4ba68a0` |
| Current preparation HEAD | `3dc7391` |
| UAT fix commit | `beb8f70` |
| Initial working tree | Clean |
| Laravel/PHP | 13.25.0 / 8.3.16 |
| Database | MySQL 8.4.3 |
| Migration | 26 Ran, 0 Pending |
| Full test | 297 passed, 2470 assertions, 0 failed, 0 skipped |
| Frontend build | PASS, Vite 8.0.16, 57 modules |
| `git diff --check` | PASS |

## 2. Environment

Environment yang tersedia dalam sesi ini adalah local/internal development pilot, bukan staging production-like:

- APP environment local dan debug aktif.
- MySQL/database session/cache tersedia.
- Mail memakai log transport, sehingga email delivery nyata belum dapat divalidasi.
- HTTPS, secure production cookie, domain, TLS, monitoring, dan external backup target belum tersedia.
- Frontend production build berhasil.

Status: **INFRA ACTION REQUIRED** untuk smoke pada staging HTTPS dengan APP_DEBUG false sebelum go-live. Production real tidak digunakan sebagai tempat UAT.

## 3. Release Commit

- UAT Build 1 / Candidate UAT source commit: `064a117` (`chore: finalize UAT candidate and resolve release blockers`).
- Final UAT commit: **pending human execution dan setiap retest/fix yang mungkin diperlukan**.
- Candidate commit bersifat immutable; perubahan source setelah ini harus berasal dari reproduced UAT issue dan menghasilkan UAT Build berikutnya.
- Tidak ada push atau deployment production pada tahap ini.

### Technical Preflight After Candidate Commit

| Check | Actual Result |
|---|---|
| Full suite run 1 | 297 passed, 2470 assertions, 0 failed, 0 skipped |
| Full suite run 2 | 297 passed, 2470 assertions, 0 failed, 0 skipped |
| Frontend build | PASS, Vite 8.0.16, 57 modules; CSS 28.47 kB, JS 89.97 kB |
| Composer production audit | 0 advisory |
| npm full / production audit | 0 / 0 vulnerability |
| Migration | 26 Ran, 0 Pending |
| Config cache | PASS |
| Route cache | PASS |
| View cache | PASS |
| Final local cache clear | PASS |
| Post-provision full suite | 297 passed, 2470 assertions, 0 failed, 0 skipped |
| Post-provision browser smoke | PASS untuk login/landing 5 role, admin/HR, existing/new employee, E-Card, dokumen, scanner, dan report; 0 console error, 0 broken image, 0 horizontal overflow pada halaman yang diuji |

Read-only integrity preflight menghasilkan 0 duplicate attendance group, 0 duplicate participant group, 0 employee dengan multiple active QR, 0 inactive employee dengan active QR, dan 0 eligible verified employee yang kehilangan active QR.

## 4. UAT Roles

| UAT ID | Role | Planned Scope | Account Status |
|---|---|---|---|
| UAT-SA-01 | Super Admin | Dashboard, employee, unit, position, E-Card | Provisioned local, active |
| UAT-HR-01 | HR Admin | Verification, documents, import/export, reports | Provisioned local, active |
| UAT-EMP-EX-01 | Existing Employee | Mobile self-service, E-Card, activity, documents, account | Provisioned local; verified, NUP valid, 1 active QR |
| UAT-EMP-NEW-01 | New Employee | Invitation/onboarding/profile/verification | Provisioned local; draft, NUP null, 0 active QR |
| UAT-PAN-01 | Panitia | Event context, QR/manual attendance | Provisioned local, active |

Jumlah tester manusia aktual: **0**. Temporary password dibuat secara acak dan diberikan hanya melalui output operator lokal; nilainya tidak dicatat dalam repository. Tidak ada data pribadi nyata yang digunakan.

## 4A. Infrastructure Action Register

| Item | Status | Evidence / Required Action |
|---|---|---|
| Local technical environment | READY | Automated preflight PASS; hanya untuk local/internal pilot |
| SMTP credentials and delivery | PENDING EXTERNAL | Application mail flow teruji; external inbox delivery belum diuji |
| Staging / hosting | PENDING EXTERNAL | Environment production-like belum disediakan |
| Domain and DNS | PENDING EXTERNAL | Tidak ada domain UAT/production yang dapat diverifikasi |
| TLS certificate / HTTPS | PENDING EXTERNAL | Local memakai HTTP; production wajib HTTPS dan secure cookie |
| Monitoring / alerting | PENDING EXTERNAL | Checklist tersedia; server dan destination belum dipilih |
| Physical QR scanner | PENDING EXTERNAL | USB/HID device dan human operator belum tersedia |

`LOCAL HUMAN UAT` siap dimulai menggunakan account dan data dummy yang telah diprovision. `PRODUCTION-LIKE UAT` tetap membutuhkan staging HTTPS, secure cookie, SMTP bila mandatory, dan monitoring evidence.

## 5. Test Data

Provisioning aktual pada 14 Agustus 2026 dilakukan di environment `local` melalui model dan service aplikasi dalam database transaction, bukan melalui seeder atau raw insert:

- `UAT-EMP-EX-01`: employee ID 15, NUP synthetic `9900000001`, verified/active, profile sengaja belum lengkap, dan tepat satu active QR.
- `UAT-EMP-NEW-01`: employee ID 16, NUP null, verification `draft`, dan tanpa active QR.
- `UAT Kegiatan Internal 001`: event ID 6, status active, 6 participant, 0 attendance awal.
- Employee ID 6 disiapkan sebagai eligible non-participant; employee ID 5 dapat digunakan untuk manual attendance.
- File dummy non-sensitive tersedia di system temporary directory di luar repository dengan nama `uat-dummy-document.pdf`; PDF satu halaman telah dirender dan diperiksa.
- Seeder development tidak dijalankan. Tidak ada NIK, rekening, BPJS, pajak, KK, atau private document asli yang digunakan.

Shared UAT data tidak boleh di-reset dengan `migrate:fresh`. Raw QR token tidak dicatat dalam dokumen ini.

## 6. Scenario Summary

Paket menyediakan **55 task-based scenarios**:

| Group | Prepared | Human Executed | PASS | PASS WITH NOTE | FAIL | BLOCKED |
|---|---:|---:|---:|---:|---:|---:|
| Authentication | 7 | 0 | 0 | 0 | 0 | 0 |
| Super Admin | 8 | 0 | 0 | 0 | 0 | 0 |
| HR Admin | 6 | 0 | 0 | 0 | 0 | 0 |
| Existing Employee | 9 | 0 | 0 | 0 | 0 | 0 |
| New Employee | 6 | 0 | 0 | 0 | 0 | 0 |
| Panitia/Attendance | 10 | 0 | 0 | 0 | 0 | 0 |
| Import | 4 | 0 | 0 | 0 | 0 | 0 |
| Reports | 3 | 0 | 0 | 0 | 0 | 0 |
| Authorization/Privacy | 2 | 0 | 0 | 0 | 0 | 0 |
| **Total** | **55** | **0** | **0** | **0** | **0** | **0** |

Zero FAIL/BLOCKED di tabel ini berarti belum ada human execution, bukan UAT PASS.

## 6A. Codex-Assisted UAT Execution

Execution label: **CODEX-ASSISTED UI UAT**. Ini adalah automated browser acceptance pada local non-production environment dan tidak menggantikan human acceptance.

| Result | Count |
|---|---:|
| P0 total | 31 |
| P0 executed | 31 |
| PASS | 28 |
| PASS WITH NOTE | 3 |
| FAIL | 0 |
| Application BLOCKED | 0 |
| External pending | Physical QR/2D scanner, external SMTP, staging/hosting/domain/TLS/monitoring |

Browser flow mencakup login lima role, guest denial, create existing/draft employee, existing employee E-Card/document, new employee profile submit -> HR document review/NUP approval -> E-Card, event scanner QR/duplicate/revoked/non-participant/manual, attendance/report/export, import valid/invalid, dan direct URL authorization checks. Mobile employee flow diuji pada 390x844 dan 430x932.

Tiga issue HIGH ditemukan dan ditutup sebagai `RETEST PASS`:

1. UAT-001: verified employee tidak dapat mengunggah dokumen miliknya.
2. UAT-002: confirmation modal tidak meneruskan submit.
3. UAT-003: profile submission tidak konsisten masuk antrean HR dan belum mempunyai NUP assignment pada approval.

Post-fix evidence:

| Gate | Actual Result |
|---|---|
| Targeted regression | 127 tests, 1095 assertions, PASS |
| Full suite #1 | 299 tests, 2489 assertions, 0 failed, 0 skipped |
| Full suite #2 | 299 tests, 2489 assertions, 0 failed, 0 skipped |
| Frontend build | PASS; Vite 8.0.16; 57 modules |
| Composer production audit | 0 advisory |
| npm full / production audit | 0 / 0 vulnerability |
| Migration | 26 Ran, 0 Pending |
| Config/route/view cache | PASS; cache dibersihkan kembali |
| Browser console | 0 application warning/error pada tab UAT |
| Data integrity | 0 duplicate attendance, 0 duplicate participant, 0 multiple active QR, 0 inactive employee with active QR, 0 eligible employee without active QR |

`UAT-EMP-008` berstatus `PASS WITH NOTE`: halaman/form password dan automated password regression lulus, tetapi final browser password-change submit tidak dilakukan Codex karena memerlukan human handoff. `UAT-PAN-003` dan `UAT-PAN-010` juga `PASS WITH NOTE`: application HID/Enter/focus flow lulus, sedangkan physical scanner belum tersedia.

## 7. Super Admin

Prepared: login/landing, dashboard comprehension, employee search/filter/reset/detail, existing/new employee creation, unit/jabatan constraints, dan E-Card preview. Human result: **PENDING**.

## 8. HR Admin

Prepared: daily employee work, approve/double-verification/reject-resubmit, document review, filtered export, dan E-Card identity check. Human result: **PENDING**.

## 9. Existing Employee

Prepared: direct employee landing without forced onboarding, home 390/430 px, five-item bottom navigation, E-Card, activities, documents ownership, account/profile, password change, dan optional completion wording. Human result: **PENDING**.

Stage 7 technical evidence already showed employee pages render without overflow at 390/430 px, but this does not replace usability acceptance.

## 10. New Employee

Prepared: first login, six-step wizard, partial draft persistence, submit, HR approval/NUP/QR, dan E-Card availability after verification. Human end-to-end result: **PENDING**.

## 11. Panitia

Prepared: role landing, event/participant context, valid QR, duplicate, invalid, revoked, non-participant, manual attendance, list/filter, dan rapid HID scanner sequence. Human result: **PENDING**.

## 12. Mobile UAT

Responsive matrix includes 390, 430, 768, 1024, dan 1440 px. Stage 7 automated/browser smoke passed 390/430 employee UI and Stage 8 guest login smoke passed 390 px with no console error, broken image, or overflow. Actual human mobile feedback: **PENDING**.

Stage 9 technical login preflight terhadap commit yang sama:

| Viewport | Root/Login | Form | Overflow | Broken Image | Console Warning/Error |
|---|---|---|---:|---:|---:|
| 1440x900 | Root redirected to `/login` | Visible | 0 | 0 | 0 |
| 430x932 | Root redirected to `/login` | Visible | 0 | 0 | 0 |
| 390x844 | Root redirected to `/login` | Visible | 0 | 0 | 0 |

Ini hanya technical preflight, bukan penilaian usability manusia atau bukti bahwa keyboard mobile nyata telah diuji.

## 13. Scanner Pilot

Physical QR scanner result: **NOT EXECUTED - DEVICE/HUMAN REQUIRED**. Planned generic device label: `USB QR scanner - keyboard HID`. Camera browser scanner is outside design and must not be introduced.

## 14. Import / Export

Codex-assisted UI result: **PASS**. Modal/template download event tersedia; import 3 row valid menghasilkan 3 created, 0 skipped, 0 failed, dan 2 QR; dua row invalid menghasilkan pesan per baris tanpa exception teknis. Export report dan attendance dapat dibuka, filter/event benar, dan tidak ditemukan raw QR token, blind index, atau ciphertext. Human usability acceptance tetap **PENDING**.

## 15. Report Validation

Codex-assisted aggregate result: **PASS** dengan 6 participant, 5 QR attendance, 1 manual attendance, total hadir 6, dan duplicate 0. UI report dan kedua workbook export konsisten. Evidence ini termasuk dalam operator acceptance 16 Agustus 2026.

## 16. Issues

Codex-assisted UAT menemukan 3 issue HIGH dan seluruhnya berstatus `RETEST PASS`. Open application severity: BLOCKER 0, CRITICAL 0, HIGH 0. Detail ada di `docs/uat/uat-issue-register.md`. Belum ada issue yang dilaporkan tester manusia karena human execution belum dimulai.

Release gates carried from Stage 8/9.5:

- `DEP-001` Composer advisory: FIXED, final audit 0.
- `DEP-002` npm advisory: FIXED, final audit 0.
- `DEP-003` isolated MySQL restore drill: FIXED, aggregate/migration/NIK/QR checks PASS.
- `DEP-004` public orphan employee files: FIXED, 6 quarantined dan public remaining 0.
- SMTP, hosting/domain/TLS, monitoring, access-log redaction, RPO/RTO approval, dan production-like secure session: PENDING EXTERNAL.

Infrastructure items are not counted as new UAT bugs. Mandatory production infrastructure remains a Stage 10 entry gate.

## 17. Retest

Codex retest untuk UAT-001/UAT-002/UAT-003 lulus melalui browser UI dan automated regression. Fix source/test dicatat pada commit `beb8f70`. Human acceptance terhadap hasil ini tetap diperlukan sebelum sign-off.

## 18. Deferred Items

| Item | Classification | Release Treatment |
|---|---|---|
| Composer/npm advisories | Remediated in Stage 9.5 | FIXED; final audit 0 |
| MySQL restore drill | Isolated drill completed | FIXED; 26 Ran / 0 Pending |
| Public orphan employee files | Quarantined in private storage | FIXED; public remaining 0 |
| SMTP, domain, TLS, monitoring, service account | Infrastructure Action | Required before Stage 10 deployment |
| Invitation token access-log redaction | Infrastructure Action | Required before go-live |
| RPO/RTO approval | Infrastructure Action | Required before go-live |
| PDF E-Card | Post-v1 | Do not test as available feature |
| Bulk import/XLSX scaling beyond current cap | Accepted Operational Limitation | Measure before redesign |
| Self-host fonts/CSP strict | Post-v1/security improvement | Track separately; no silent redesign |

## 19. Human Acceptance

Pada 16 Agustus 2026, authorized operator meninjau dan menerima evidence Codex-assisted UAT sebagai UAT/Pilot Internal Tahap 9. Acceptance tidak diperlakukan sebagai klaim bahwa operator mengulang seluruh 31 scenario melalui browser. Pernyataan eksplisit operator menyetujui kelanjutan ke Tahap 10, menerima tidak adanya open application BLOCKER/CRITICAL/HIGH, dan menerima infrastructure actions serta PDF E-Card post-v1 sebagai deferred non-application items.

## 20. Release Recommendation

Current decision: **HUMAN UAT ACCEPTANCE APPROVED**.

The application is technically green on the post-fix local baseline, seluruh 31 P0 telah memiliki Codex-assisted result, tidak ada application FAIL/BLOCKED tersisa, dan operator telah memberikan acceptance eksplisit. Sistem dapat masuk Tahap 10 Release Candidate preparation dengan physical scanner, external SMTP, production hosting/domain/TLS, dan monitoring tetap sebagai documented infrastructure actions yang wajib diselesaikan sebelum Go-Live.

Required next actions:

1. Finalisasi dan commit dokumentasi acceptance.
2. Tetapkan exact release candidate SHA setelah gate teknis Tahap 10 Mode A lulus.
3. Validate physical scanner sebelum attendance production pertama.
4. Complete external SMTP dan production-like HTTPS/TLS checks sebelum Go-Live.

Files:

- `docs/uat/uat-test-scenarios.md`
- `docs/uat/uat-issue-register.md`
- `docs/uat/post-v1-backlog.md`
- `docs/uat/uat-signoff.md`

Tahap 10 Mode A Release Candidate preparation diizinkan oleh acceptance ini. Production execution tetap memerlukan approval terpisah.
