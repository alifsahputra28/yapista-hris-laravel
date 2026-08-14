# YAPISTA HRIS UAT Sign-off

Dokumen ini adalah draft. Codex tidak memberi acceptance atas nama pengguna.

## Release Identity

| Item | Value |
|---|---|
| System | YAPISTA HRIS Core |
| Candidate UAT Commit | Assigned after technical-candidate commit |
| Final UAT Commit | Pending human execution/retest |
| Environment | Local/internal pilot; staging production-like belum tersedia |
| UAT Period | Belum dimulai |
| Technical Baseline | 297 tests, 2470 assertions, build PASS, 26 Ran / 0 Pending |

## Roles Planned

- Super Admin: `UAT-SA-01`
- HR Admin: `UAT-HR-01`
- Existing Employee: `UAT-EMP-EX-01`
- New Employee: `UAT-EMP-NEW-01`
- Panitia: `UAT-PAN-01`

Tester nyata dan account belum diprovision melalui sesi ini. Password tidak boleh dicatat di sini.

## Critical Scenarios

| Area | Scenario | Result | Notes/Evidence |
|---|---|---|---|
| Role landing | Login seluruh role menuju area yang benar | Pending |  |
| Existing employee | Login tanpa forced onboarding, E-Card tersedia | Pending |  |
| New employee | Invitation/profile/submit/HR approve/NUP/QR/E-Card | Pending |  |
| Import | Template, valid import, dan error yang mudah dipahami | Pending |  |
| Attendance | Valid QR, duplicate, invalid/revoked, manual | Pending |  |
| Report | Pilot count dan export sesuai data | Pending |  |
| Documents | Ownership, upload, preview/download | Pending |  |
| Mobile | 390 px dan 430 px employee navigation | Pending |  |

## Open And Deferred Items

- Four Stage 8 technical blockers are closed by Stage 9.5: dependency audits 0, isolated restore PASS, and public employee exposure 0.
- SMTP/TLS/production-like session checks remain infrastructure actions.
- PDF E-Card remains post-v1 unless product scope changes explicitly.
- Human UAT issue count is currently zero because execution has not started, not because scenarios passed.

## Technical Gate

- [x] Exact candidate commit recorded.
- [x] Working tree was clean at Stage 9 baseline.
- [x] Full automated test passed.
- [x] Frontend production build passed.
- [x] Migration status has 0 Pending.
- [x] Production dependency blockers closed.
- [x] Database restore drill passed.
- [x] Public employee orphan exposure closed through private quarantine.

## User Gate

- [ ] Super Admin scenarios accepted.
- [ ] HR Admin scenarios accepted.
- [ ] Existing Employee scenarios accepted.
- [ ] New Employee scenarios accepted.
- [ ] Panitia scenarios accepted.
- [ ] Mobile and physical scanner pilot accepted.
- [ ] BLOCKER/CRITICAL/HIGH unresolved = 0.

## Decision

Current package status: **HUMAN UAT EXECUTION REQUIRED**.

Select only after human execution and authorized review:

- [ ] ACCEPTED FOR RELEASE CANDIDATE
- [ ] ACCEPTED WITH MINOR DEFERRED ITEMS
- [ ] REQUIRES RETEST
- [ ] REJECTED

## Human Approval

| Responsibility | Name/Identifier | Decision | Date | Notes |
|---|---|---|---|---|
| Business/System Owner |  |  |  |  |
| HR Representative |  |  |  |  |
| Technical Representative |  |  |  |  |

Do not start Stage 10 until the User Gate is signed and every release blocker is closed.
