# YAPISTA HRIS Regression & Edge Case Testing

Audit date: 14 August 2026 (Asia/Jakarta)

## 1. Baseline

- Branch: `main`.
- HEAD: `9f7ca7dcc83728d06b3b0cabc96e1cc8a53747b0` (`9f7ca7d`).
- Working tree before Stage 7: clean.
- Migration: 26 Ran, 0 Pending.
- Baseline test: 274 passed, 2,211 assertions, 0 failed, 0 skipped; test duration 85.081 seconds.
- Baseline build: PASS, Vite 8.0.10, 56 modules; CSS 28.47 kB (gzip 5.58 kB), JS 86.49 kB (gzip 31.55 kB), build 11.76 seconds.
- All Stage 1-6 documents were present and read before targeted testing.

No migration, seeder, package installation/update, production data, development employee data, document, or QR token was changed in Stage 7.

## 2. Test Strategy

Testing followed normal, negative, boundary, cross-role, duplicate/repeated request, and empty/null paths. The existing 274-test suite was treated as the primary regression net; Stage 7 added 23 behavior tests and expanded malformed QR coverage rather than duplicating established tests.

The new and expanded tests execute 105 explicit request/data-case iterations plus a deterministic 100-row import smoke fixture. True distributed concurrency was not introduced; repeated requests, transaction behavior, and database unique constraints remain the race-condition evidence for NUP, QR, participants, and attendance.

## 3. Role Matrix

Legend: `Login` = guest redirected to login, `Own` = self-service resources only, `Scanner` = operational scanner scope only, `Admin` = HR/super-admin scope, `Denied` = backend 403.

| Module | Guest | Pegawai | Panitia | HR Admin | Super Admin |
|---|---|---|---|---|---|
| Dashboard | Login | Own | Scanner | Admin | Admin |
| Employee | Login | Denied | Denied | Admin | Admin |
| Verification | Login | Denied | Denied | Admin | Admin |
| Invitation | Login | Denied | Denied | Admin | Admin |
| Profile | Login | Own | Account only | Account + Admin | Account + Admin |
| Family / Education / Certification / Administration | Login | Own, ownership enforced | Denied | View through employee context | View through employee context |
| Documents | Login | Own | Denied | Admin | Admin |
| QR regeneration | Login | Denied | Denied | Admin | Admin |
| E-Card | Login | Own | Denied | Admin preview | Admin preview |
| Events | Login | Own activity view | Scanner only | Admin | Admin |
| Participants | Login | Own activity context | Scanner only | Admin | Admin |
| Scanner / Attendance | Login | Denied | Allowed | Allowed | Allowed |
| Reports | Login | Denied | Denied | Admin | Admin |
| Import / Export | Login | Denied | Denied | Admin | Admin |

The Stage 7 centralized access tests execute 39 route-role scenarios across guest, pegawai, panitia, HR, and super admin. Existing ownership tests remain active for family, education, certification, administrative details, documents, photos, QR, and E-Card.

## 4. Authentication

- Targeted result: 28 tests, 158 assertions, all passed.
- Covered valid and invalid credentials, empty email/password combinations, oversized credentials, inactive accounts, remember-me, logout, protected navigation after logout, guest-only login, root and post-login role redirects.
- Five failed attempts trigger the existing limiter; a subsequent blocked attempt is safe. A valid login after four failures clears the limiter.
- Critical mutation routes retain `web` middleware and rendered employee, event, and import forms contain CSRF tokens.
- Laravel bypasses CSRF rejection while `runningUnitTests()` is true, so Stage 7 verifies middleware/form wiring rather than manufacturing a brittle 419 integration environment.

## 5. Employee / NUP

- NUP remains `employees.employee_number`, plaintext, unique, exactly 10 numeric characters, with leading zero preserved.
- Existing tests cover null, 9/10/11 digits, alphabetic/dotted input, duplicate NUP, verified NUP removal prevention, draft-to-verified transition, and cross-unit position rejection.
- Stage 7 covers 255/256-character employee name boundaries, forged `role`, `verified_by`, `verification_status`, and `qr_token` request fields, plus inactive-to-active QR lifecycle.
- An eligible employee reactivation creates one new active token while retaining the revoked token as history; no duplicate active QR is produced.

## 6. Verification

- Existing submitted/verified/rejected behavior, KTP requirements, invalid/missing NUP, rejection notes, and role denial remain passed.
- Double approve is now explicit: first request verifies and creates one QR; second request returns a business error, preserves verified state, and does not create a second token.
- Existing employees with valid NUP remain official/verified and eligible without requiring profile completion 100%.

## 7. Profile

- Empty family, education, certification, administrative detail, documents, and photo states render safely through existing tests.
- Ownership and locked profile behavior remain covered for all self-service child resources.
- Sensitive family NIK, bank, tax, BPJS, education, and certification numbers remain encrypted/masked and hidden from serialization.
- Date, phone, nullable, leading-zero, highest-education, certification-expiry, and one-to-one administration rules remain passed.

## 8. Sensitive Data / NIK

- NIK remains encrypted with HMAC blind-index exact lookup and masked non-edit presentation.
- Existing tests cover null, format, uniqueness, exact versus partial search, backfill idempotency, corrupt ciphertext verification, authorization, and non-exposure in reports/ID Card.
- Search, pagination, and rendered employee HTML were exercised with wildcard characters, quotes, long text, Unicode, and XSS payloads. No SQL exception, raw script element, ciphertext, or blind index was exposed.
- Raw NIK is not introduced into GET filters or pagination links by Stage 7.

## 9. Documents

- Existing tests cover valid private upload, invalid/oversized photo and document input, replacement, deletion, owner/HR access, cross-owner and panitia denial, path traversal, legacy migration, and missing physical file 404.
- No public path endpoint or direct filesystem identifier was added.
- Browser smoke found zero broken images on the audited pages.

## 10. QR / E-Card

- Existing QR tests cover random encrypted/hashed tokens, strict payload, regeneration/revocation, one-active-token consolidation, role denial, own E-Card, photo fallback, unavailable states, and no raw token in HTML.
- Scanner malformed coverage now includes empty input, 9/10-digit NUP, 16-digit NIK, bare `YAPISTA`, empty token prefix, unknown token, a 4,096-character token, internal newline, and modified whitespace.
- Revoked QR remains invalid; regenerated QR remains valid; direct NUP/NIK forgery is rejected.
- E-Card at 430 px rendered photo, name, position, unit, plaintext NUP, status, and QR without overflow or broken assets.

## 11. Events / Participants

- Event validation now has explicit release tests for empty and 256-character names, impossible dates, equal/reversed times, 255-character names, past dates under the current allowed rule, and stale update/delete 404 responses.
- Participant generation remains limited to eligible employees with valid NUP. Duplicate manual addition remains idempotent and the event+employee unique constraint remains the last defense.
- Event deletion with attendance history remains blocked in both application and database tests.

## 12. Attendance

- Targeted result: 22 tests, 210 assertions, all passed.
- Covered active/inactive event states, verified/active employee rules, participant and cancelled-participant checks, QR and manual success, QR/manual cross-duplicates, rapid duplicate constraint simulation, and non-duplicate database exception propagation.
- Duplicate scan returns `already_attended` and retains one attendance row. The unique event+employee index remains the race-condition backstop.
- Manual attendance remains `manual`; new automatic scans remain `qr`; historical `barcode` rows remain readable/reportable.
- Summary and export continue to exclude cancelled participants.

## 13. Import / Export

- Import targeted result: 13 tests, 91 assertions, all passed.
- Covered XLSX/XLS/CSV, verified employee and draft employee flows, invalid file, wrong/duplicate/header-only files, blank rows, duplicate NUP within an import, invalid row rollback, and unauthorized roles.
- Extra `Role`, `Verification Status`, `QR Token`, `Password`, and `NIK Lookup` columns are rejected before mutation.
- Deterministic 100-row import completed with 100 employees, 100 invitations, and 100 active QR tokens, with zero failure/skip and no duplicate state.
- Export tests cover filtered employee/event/attendance files, valid empty workbooks, cancelled participants, sensitive-column exclusion, and formula values stored as text.

## 14. Dashboard / Reports

- Empty dashboard, real aggregate, event percentage zero, filtered attendance, and cancelled-participant summaries remain passed.
- Empty employee and event exports contain headers only and return valid XLSX responses.
- Employee list handles `page=0`, `page=-1`, very large pages, non-numeric pages, invalid filter IDs, wildcard characters, quotes, 2,000-character search, and Unicode without HTTP 500.

## 15. Database Constraints

Existing isolated database tests remain green for:

- unique NUP and NIK lookup;
- unique institution name;
- unique position per institution;
- one employee per user;
- one administrative detail per employee;
- unique participant per event+employee;
- unique attendance per event+employee;
- required position institution and protected event attendance history.

Stage 7 added application-level case-insensitive normalized checks for institution and position names. This makes behavior consistent in SQLite tests and MySQL production (`utf8mb4_0900_ai_ci`) while retaining the Stage 4 database indexes.

## 16. Responsive Smoke QA

Browser QA used the local application and existing synthetic/demo accounts. It did not modify employee data.

| Viewport | Pages | Result |
|---|---|---|
| 1440x900 | Login, Dashboard, Employees, Verification, Events, Employee Report, Import modal | PASS; no overflow, broken image, or server error |
| 768x900 | Dashboard, Employees, Events | PASS; no overflow or broken image |
| 430x932 | Employee Home, Activities, E-Card, Documents, Account, Profile | PASS; app bar/bottom nav visible, sidebar hidden, no overflow |
| 390x844 | Login and the same six employee pages | PASS; no overflow, broken image, or blocked content |

The import modal opened with the expected title and no internal overflow. Browser console errors/warnings: 0. Broken image/asset observations: 0. The application log received no new error after 13:12; older retained entries include historical MySQL-unavailable and already-fixed testing failures and were not deleted.

## 17. Regression Findings

| ID | Severity | Area | Scenario | Expected | Actual | Fix | Test | Status |
|---|---|---|---|---|---|---|---|---|
| REG-001 | MEDIUM | Master Data | Unit/jabatan duplicate differs only by case | Validation error | Accepted by SQLite application test | Added `LOWER(TRIM(name))` scoped validation while retaining unique rules/indexes | `test_master_names_are_trimmed_and_cannot_bypass_uniqueness_by_case` | FIXED |
| TST-001 | INFO | Test fixture | Panitia accesses nonexistent document | Authorization evidence | Model binding returned safe 404 before role middleware assertion | Test now uses an existing synthetic document | `test_panitia_cannot_access_employee_administration_or_sensitive_routes` | FIXED (test only) |

Severity gate: BLOCKER 0, CRITICAL 0, HIGH unresolved 0, MEDIUM unresolved 0, LOW unresolved 0.

## 18. Fixes

- `InstitutionController` rejects case-insensitive normalized duplicate names with the existing friendly message.
- `PositionController` applies the same rule within the selected institution; equal names in different institutions remain allowed.
- No business workflow, migration, package, QR payload, NUP rule, authorization model, or storage architecture changed.

## 19. Full Suite

Targeted groups after fixes:

| Group | Tests | Assertions | Result |
|---|---:|---:|---|
| Auth | 28 | 158 | PASS |
| Employee | 198 | 1,708 | PASS |
| Security | 32 | 222 | PASS |
| QR | 28 | 214 | PASS |
| Attendance | 22 | 210 | PASS |
| Import | 13 | 91 | PASS |
| Report | 12 | 79 | PASS |

Final full suites with identical test composition:

1. 297 passed, 2,470 assertions, 0 failed, 0 skipped; 31.579 seconds.
2. 297 passed, 2,470 assertions, 0 failed, 0 skipped; 32.264 seconds.

Final frontend build: PASS, 56 modules; CSS 28.47 kB (gzip 5.58 kB), JS 86.49 kB (gzip 31.55 kB), 1.48 seconds. Final migration status: 26 Ran, 0 Pending.

## 20. Remaining Risk

- True multi-process/distributed race tests were not run. Repeated-request simulation, transactions, row locks where implemented, and database unique constraints cover realistic release risk in this environment.
- Stale edit forms use current last-write-wins behavior; no optimistic locking requirement exists. Missing/deleted route-bound records return 404 and relationship constraints prevent orphan core records.
- CSRF 419 itself is not generated by Laravel's testing runtime; critical routes/forms retain standard web middleware and tokens.
- Direct database writes under a future case-sensitive production collation could differ from application normalization. Current production baseline is MySQL `utf8mb4_0900_ai_ci`; deployment Stage 8 should preserve/verify it.
- PDF E-Card download remains an explicit post-v1 placeholder and was not implemented.

Release readiness: **READY WITH MINOR DEFERRED ISSUES**.

The system meets the Stage 7 acceptance gate and is ready to enter **Stage 8 - Backup & Deployment Readiness** when separately instructed.
