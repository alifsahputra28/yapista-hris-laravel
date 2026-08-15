# YAPISTA HRIS UAT Sign-off

Dokumen ini mencatat acceptance yang diberikan secara eksplisit oleh operator. Codex tidak memberi acceptance atas nama pengguna.

## Release Identity

| Item | Value |
|---|---|
| System | YAPISTA HRIS Core |
| Candidate UAT Commit | `064a117` |
| UAT Fix Commit | `beb8f70` |
| Environment | Local/internal pilot; staging production-like belum tersedia |
| UAT Period | Codex-assisted execution 14 Agustus 2026; operator acceptance 16 Agustus 2026 |
| Technical Baseline | Double post-fix suite PASS: 299 tests/2489 assertions each; build PASS; 26 Ran / 0 Pending; dependency audit 0 |

## Roles Planned

- Super Admin: `UAT-SA-01`
- HR Admin: `UAT-HR-01`
- Existing Employee: `UAT-EMP-EX-01`
- New Employee: `UAT-EMP-NEW-01`
- Panitia: `UAT-PAN-01`

Kelima account telah diprovision pada environment local/internal pilot. Tidak ada sesi klik manusia tambahan yang diklaim; operator menerima evidence Codex-assisted sebagai UAT/Pilot Internal Tahap 9. Password tidak dicatat di dokumen ini.

## Critical Scenarios

| Area | Scenario | Result | Notes/Evidence |
|---|---|---|---|
| Role landing | Login seluruh role menuju area yang benar | ACCEPTED | Codex-assisted UI UAT PASS |
| Existing employee | Login tanpa forced onboarding, E-Card tersedia | ACCEPTED | Codex-assisted UI UAT PASS |
| New employee | Profile/submit/HR approve/NUP/QR/E-Card | ACCEPTED | Codex-assisted UI UAT dan retest PASS |
| Import | Template, valid import, dan error yang mudah dipahami | ACCEPTED | Codex-assisted UI UAT PASS |
| Attendance | Valid QR, duplicate, invalid/revoked, manual | ACCEPTED WITH NOTE | Application scanner flow PASS; perangkat fisik pending Tahap 10 |
| Report | Pilot count dan export sesuai data | ACCEPTED | Codex-assisted UI UAT PASS |
| Documents | Ownership, upload, preview/download | ACCEPTED | Codex-assisted UI UAT dan retest PASS |
| Mobile | 390 px dan 430 px employee navigation | ACCEPTED | Codex-assisted browser QA PASS |

## Open And Deferred Items

- Four Stage 8 technical blockers are closed by Stage 9.5: dependency audits 0, isolated restore PASS, and public employee exposure 0.
- SMTP/TLS/production-like session checks remain infrastructure actions.
- PDF E-Card remains post-v1 unless product scope changes explicitly.
- PDF E-Card remains post-v1 and is explicitly accepted as non-blocking.

## Technical Gate

- [x] Exact candidate commit recorded.
- [x] Working tree was clean at Stage 9 baseline.
- [x] Full automated test passed.
- [x] Frontend production build passed.
- [x] Migration status has 0 Pending.
- [x] Production dependency blockers closed.
- [x] Database restore drill passed.
- [x] Public employee orphan exposure closed through private quarantine.
- [x] Config, route, and view cache preflight passed; local cache cleared afterward.
- [x] Double full automated suite passed without flakiness.
- [x] Lima account UAT, existing/new employee state, event, 6 participant, dan dummy document telah diprovision di local non-production.
- [x] Browser smoke lima role dan halaman inti selesai tanpa console error, broken image, atau horizontal overflow pada halaman yang diuji.

## User Gate

- [x] Super Admin scenarios accepted.
- [x] HR Admin scenarios accepted.
- [x] Existing Employee scenarios accepted.
- [x] New Employee scenarios accepted.
- [x] Panitia application scenarios accepted.
- [x] Mobile application result accepted; physical scanner remains documented infrastructure action.
- [x] BLOCKER/CRITICAL/HIGH application issue unresolved = 0.

## Decision

Current package status: **HUMAN UAT ACCEPTANCE APPROVED**.

Select only after human execution and authorized review:

- [ ] ACCEPTED FOR RELEASE CANDIDATE
- [x] ACCEPTED WITH MINOR DEFERRED ITEMS
- [ ] REQUIRES RETEST
- [ ] REJECTED

## Human Approval

| Responsibility | Name/Identifier | Decision | Date | Notes |
|---|---|---|---|---|
| Authorized YAPISTA HRIS Operator | Operator acceptance in Codex task | APPROVED | 16 Agustus 2026 | Menerima Codex-assisted UAT sebagai Pilot Internal Tahap 9 dan menyetujui kelanjutan ke Tahap 10 |
| HR Representative | Tidak dinyatakan terpisah | Covered by authorized operator acceptance | 16 Agustus 2026 | Tidak mengklaim identitas atau tanda tangan tambahan |
| Technical Representative | Codex-assisted evidence | EXECUTED | 14 Agustus 2026 | 31 P0; 28 PASS; 3 PASS WITH NOTE; 0 FAIL; 0 application BLOCKED |

Stage 10 Release Candidate preparation boleh dimulai. Production Go-Live tetap memerlukan operator approval terpisah dan penyelesaian infrastructure actions.
