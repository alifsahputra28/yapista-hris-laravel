# Employee Work Fields UX

Audit and execution: 31 August 2026, Asia/Jakarta.

## Before / After

| Field | Before | After |
| --- | --- | --- |
| Unit Kerja | Native Bootstrap select | Same native select; `Cari atau pilih unit kerja`; required marker and linked validation error |
| Jabatan | All active positions regardless of unit | Disabled before choosing unit; only matching positions; reset on unit change; explicit disabled empty state |
| Jenis Pegawai | Eight-option select | Retained select because options exceed four; labels/values reuse existing import/validation constant |

Actual types: Guru, Dosen, Tenaga Kependidikan, Staff Yayasan, Security, Cleaning Service, Driver, Teknisi.

Seven active units appeared in the local browser. No searchable select is used in application views/scripts;
a bundled but unused Choices asset exists. Native Bootstrap is retained with no dependency installation.

The shared create/edit partial server-renders the matching position list and existing/old selection before
JavaScript executes. A non-rendered template supplies only master-data position IDs, unit IDs and names.
Client-side filtering does not request an API or fetch employee information. Empty state:
`Belum ada jabatan pada unit kerja ini.`

Edit also retains the employee's assigned unit/position if those master records have since been deactivated.
This does not reactivate records or change the backend validation rule. Create still lists active master data.

The existing `Rule::exists('positions', 'id')->where('institution_id', ...)` is unchanged.
Only six validation messages were added for these three fields, in Indonesian. NUP, verification, QR,
authorization, import mappings, storage, routes and migrations are unchanged. No custom CSS was needed.

## Acceptance Evidence

Actual browser UI with an existing Super Admin session; environment confirmed `local` before mutation.
One synthetic employee was created without email, NIK, NUP, photo, or sensitive data:
`UAT UX Unit Jabatan 20260831` (employee ID 18).

| Scenario | Result |
| --- | --- |
| Create initial state | PASS: Jabatan disabled, `Pilih unit kerja terlebih dahulu` |
| SMK unit selection | PASS: only Guru, Kepala Sekolah, Operator Sekolah, Staff TU, Wakil Kepala Sekolah |
| Change to Kantor Yayasan | PASS: previous position reset; only that unit's positions shown |
| Create save | PASS via UI: SMK / Guru / Guru; success flash; Draft and Belum Registrasi |
| Edit open | PASS: existing SMK / Guru / Guru selected |
| Edit change/save | PASS via UI: Kantor Yayasan / Staff IT / Teknisi; updated flash and list row verified |
| Desktop 1440x900 | PASS: field alignment/spacing, 0 horizontal overflow, 0 broken image |
| Mobile 390x844 | PASS: fields stacked/full available width; about 309.6px control width; 0 overflow |
| Mobile 430x932 | PASS: fields about 349.6px wide; 0 overflow; edit submitted successfully |
| Console | 0 errors and warnings returned by browser log inspection |
| Empty unit and invalid pair | PASS through PHP/JS tests; no new empty master unit was created for browser UAT |

The synthetic record remains local for operator inspection. No existing employee was edited.
The browser was left on the create form, with temporary viewport override reset.

## Test And Build Results

| Check | Actual result |
| --- | --- |
| New PHP tests | 7 passed, 70 assertions |
| Targeted Laravel suite | 55 passed, 481 assertions; 8.881 s |
| Targeted areas | Employee CRUD, Unit/Jabatan, functional rules, import, detail, verification, authorization |
| New Node interaction tests | 5 passed, 0 failed, 0 skipped |
| Full suite #1 | 339 tests: 338 passed, 1 failed; 2,839 assertions; 37.240 s |
| Full suite #2 | 339 tests: 338 passed, 1 failed; 2,839 assertions; 37.807 s |
| npm run build | PASS; Vite 8.0.16, 57 modules; plugin timing advisory only |
| Migration | 26 Ran, 0 Pending; none added/executed |
| git diff --check | PASS |

Both full failures are the previously observed
`DashboardInsightsTest::test_dashboard_uses_real_employee_event_and_attendance_aggregates`.
Its dashboard aggregate predicate fails. This test and dashboard implementation were not changed by
this task; no assertion was weakened and no test was skipped. Full regression is NOT green.

```text
php artisan test --filter='EmployeeWorkFieldsTest|EmployeeManagementTest|FunctionalBugRegressionTest|MasterDataTest|EmployeeImportTest|EmployeeAdminDetailTest|EmployeeVerificationTest|AuthorizationRegressionTest'
node --test tests/Frontend/employee-work-fields.test.mjs
php artisan test
php artisan test
npm run build
php artisan migrate:status
git diff --check
```

## Files / Working Tree

Changed in this task:
- `resources/views/employees/_form.blade.php`
- `app/Http/Controllers/EmployeeController.php` (edit option presentation and messages only)

Added:
- `public/assets/js/employee-work-fields.js`
- `tests/Feature/EmployeeWorkFieldsTest.php`
- `tests/Frontend/employee-work-fields.test.mjs`
- This report.

Existing scanner changes and the existing `NUP (Pegawai Lama)` label were preserved.
Working tree remains dirty/uncommitted, including that prior work. No push, deploy, dependency update,
new API, migration, or production mutation.

Result: EMPLOYEE FORM UNIT / POSITION / EMPLOYEE TYPE UX IMPROVED.
Release caveat: existing dashboard regression failure remains unresolved.
