# YAPISTA HRIS UAT / Pilot Internal

## 1. Baseline

Tahap 9 dimulai sebagai persiapan UAT, bukan klaim acceptance. Empat technical blockers dari Stage 8 telah ditutup pada Tahap 9.5. Human UAT dan human sign-off tetap belum tersedia dan tidak dapat digantikan automated test atau browser smoke.

Baseline aktual:

| Item | Result |
|---|---|
| Branch | `main` |
| Initial HEAD | `4ba68a0` |
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

Read-only integrity preflight menghasilkan 0 duplicate attendance group, 0 duplicate participant group, 0 employee dengan multiple active QR, 0 inactive employee dengan active QR, dan 0 eligible verified employee yang kehilangan active QR.

## 4. UAT Roles

| UAT ID | Role | Planned Scope | Account Status |
|---|---|---|---|
| UAT-SA-01 | Super Admin | Dashboard, employee, unit, position, E-Card | Not provisioned in this session |
| UAT-HR-01 | HR Admin | Verification, documents, import/export, reports | Not provisioned in this session |
| UAT-EMP-EX-01 | Existing Employee | Mobile self-service, E-Card, activity, documents, account | Not provisioned in this session |
| UAT-EMP-NEW-01 | New Employee | Invitation/onboarding/profile/verification | Not provisioned in this session |
| UAT-PAN-01 | Panitia | Event context, QR/manual attendance | Not provisioned in this session |

Jumlah tester manusia aktual: **0**. Password dan data pribadi tidak dibuat atau dicatat.

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

`LOCAL HUMAN UAT` dapat dimulai menggunakan data dummy setelah account diprovision. `PRODUCTION-LIKE UAT` tetap membutuhkan staging HTTPS, secure cookie, SMTP bila mandatory, dan monitoring evidence.

## 5. Test Data

Pilot harus menggunakan `UAT Employee 001`, `UAT Kegiatan Internal 001`, NUP dummy yang belum terpakai, QR dummy, dan file dummy non-sensitive. Event menggunakan 5-10 participant dummy. Hindari NIK, rekening, BPJS, pajak, KK, atau private document asli. Seeder development tidak dijalankan. Shared UAT data tidak boleh di-reset dengan `migrate:fresh`.

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

Prepared UAT uses official modal/template with 3-10 dummy rows, one valid file and one intentionally invalid file. Template download, summary clarity, business rules, filtered export, and workbook readability all require human execution. Current result: **PENDING**.

## 15. Report Validation

Pilot sheet requires aggregate comparison for participant, QR attendance, manual attendance, total, dan zero duplicates. No PII is recorded. Result: **PENDING**.

## 16. Issues

No actual UAT issue has been reported because no tester has executed the scenarios. `docs/uat/uat-issue-register.md` is ready.

Release gates carried from Stage 8/9.5:

- `DEP-001` Composer advisory: FIXED, final audit 0.
- `DEP-002` npm advisory: FIXED, final audit 0.
- `DEP-003` isolated MySQL restore drill: FIXED, aggregate/migration/NIK/QR checks PASS.
- `DEP-004` public orphan employee files: FIXED, 6 quarantined dan public remaining 0.
- SMTP, hosting/domain/TLS, monitoring, access-log redaction, RPO/RTO approval, dan production-like secure session: PENDING EXTERNAL.

Infrastructure items are not counted as new UAT bugs. Mandatory production infrastructure remains a Stage 10 entry gate.

## 17. Retest

No UAT fix or retest exists yet. A bug may be closed only after reproduce, minimal fix, regression test, updated UAT build commit, and original human scenario reaches `RETEST PASS`.

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

## 19. Human Feedback

Human feedback, terminology review, performance perception, empty-state comprehension, success/error clarity, and sign-off are all **PENDING**. No acceptance date or tester identity is invented.

## 20. Release Recommendation

Current decision: **HUMAN UAT EXECUTION REQUIRED**.

The application is technically green on the local baseline and the four Stage 9.5 technical blockers are closed. It is **not ready to enter Stage 10** because tester count is 0, no scenario has a human result, physical scanner/SMTP production evidence is unavailable, and sign-off remains pending.

Required next actions:

1. Provision isolated staging/internal accounts and dummy data for five roles.
2. Execute and record all critical scenarios, including physical scanner when available.
3. Register/fix/retest every BLOCKER, CRITICAL, dan HIGH UAT issue.
4. Complete external SMTP and production-like HTTPS/TLS checks when infrastructure is available.
5. Obtain authorized human sign-off against an exact final commit.

Files:

- `docs/uat/uat-test-scenarios.md`
- `docs/uat/uat-issue-register.md`
- `docs/uat/post-v1-backlog.md`
- `docs/uat/uat-signoff.md`

Tahap 10 tidak dimulai oleh dokumen ini.
