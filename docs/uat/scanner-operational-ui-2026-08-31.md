# Scanner Operational UI Review

Date: 31 August 2026 (Asia/Jakarta).
Baseline HEAD: `985f86e`. This change is uncommitted and is not a validated release candidate.

## Scope

- Replace simultaneous scanner/result/recent/manual panels with one scanner workspace and compact statistics.
- Keep the existing focus layout, header, exit link, and attendance-list link.
- Move the existing manual POST form into a Bootstrap modal. Preserve old employee selection, note, field errors, and reopen on validation failure.
- Move the existing five eager-loaded recent records into a Bootstrap right offcanvas. Successful scans update the same bounded list without polling.
- Show a non-blocking result overlay for 3,000 ms, retaining a compact last-result summary. Return focus after scans and surface closure without interfering with Bootstrap focus traps.
- Display backend operational identity fields with an icon fallback. The response has no photo URL; no new photo access was introduced.
- Mask the scanner input and clear its submitted value. No raw QR payload is added to feedback, history, or logs.
- Keep scanner resolver, secure/legacy QR, attendance service, authorization, manual endpoint, report/export, and database unchanged.

## Evidence

| Check | Actual result |
| --- | --- |
| Targeted Laravel regression | PASS: 56 tests, 586 assertions |
| Targeted coverage | Attendance hardening, secure and legacy QR, cross-format duplicates, manual attendance, event management, QR lifecycle, authorization, report consistency, XLSX export |
| New view coverage | Bootstrap surfaces, hidden overlay, private scanner input, validation state recovery, selected employee/note retention, recent limit/eager loading, manual success |
| JavaScript interaction tests | PASS: 6 tests, 0 failed, 0 skipped; Node built-in test runner, no new dependency |
| JS coverage | HID submit, processing/success/warning/error feedback, 3-second timer replacement, focus traps and return, validation reopen, safe HTTP failure, five-record recent limit |
| Full Laravel run 1 | FAIL: 332 tests; 331 passed, 1 failed; 2,769 assertions; 53.867 s |
| Full Laravel run 2 | FAIL: 332 tests; 331 passed, 1 failed; 2,769 assertions; 33.999 s |
| Build | PASS: Vite 8.0.16; 57 modules; CSS 41.08 kB, JS 89.97 kB; plugin timing advisory only |
| Composer locked production audit | 0 advisories |
| npm audit / npm audit --omit=dev | 0 / 0 vulnerabilities |
| Migration status | 26 Ran, 0 Pending; no migration executed or added |
| JS syntax / git diff --check | PASS / PASS |

Commands:

```text
php artisan test --filter='EventAttendanceHardeningTest|EventManagementTest|ReportConsistencyTest|XlsxExportTest|AuthorizationRegressionTest|EmployeeQrCodeTest'
node --test tests/Frontend/attendance-scanner.test.mjs
php artisan test
php artisan test
npm run build
composer audit --locked --no-dev
npm audit
npm audit --omit=dev
php artisan migrate:status
git diff --check
```

## Remaining Verification

**Regression blocker:** both full runs fail in
`DashboardInsightsTest::test_dashboard_uses_real_employee_event_and_attendance_aggregates`.
Its dashboard view-data predicate is false. The same test also fails when executed independently
(1 test, 5 assertions). Dashboard source and this test have no diff from HEAD. No dashboard logic
or assertion was changed to force a passing result. Root cause remains outside this scanner UI scope.

**Browser UAT pending authentication:** the local server at `http://127.0.0.1:8001/login`
is reachable, but the available browser has no authenticated session. Operator login was requested.
No credential was created/reset and no browser attendance mutation was performed.

The following are NOT yet browser-verified for this change:

- 1440x900, 1366x768, 430x932 and 390x844 scanner layout, overflow and assets.
- Actual secure/legacy scan, duplicate, invalid and nonparticipant overlays.
- Manual modal open/valid submit/invalid feedback/close-focus.
- Recent offcanvas open/close and focus return.
- Scanner-specific console errors and broken images.

The six JavaScript tests use a minimal DOM contract and mocked responses; they are not a substitute
for actual browser UAT. Physical scanner hardware was not tested.

## Files And Safety

Changed: scanner Blade, scanner-scoped block in `public/assets/css/yapista-ui.css`,
`tests/Feature/EventAttendanceHardeningTest.php`.
Added: `public/assets/js/attendance-scanner.js`, `tests/Frontend/attendance-scanner.test.mjs`, this report.

Pre-existing `resources/views/employees/_form.blade.php` change is preserved and untouched.
No dependency, route, controller, service, migration, or production change. No commit or push.

Status: IMPLEMENTED; BROWSER UAT PENDING; FULL REGRESSION NOT GREEN.
