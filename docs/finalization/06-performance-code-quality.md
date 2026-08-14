# YAPISTA HRIS Performance & Code Quality Audit

## 1. Baseline

- Audit date: 14 August 2026 (Asia/Jakarta).
- Branch: `main`.
- HEAD: `3b3ff92848da1b156d1b0c4f690b57023204d9f4` (`3b3ff92`).
- Working tree before Stage 6: clean.
- Migration: 26 Ran, 0 Pending.
- Test baseline: 273 passed, 2,172 assertions, 0 failed, 0 skipped; test duration 93.589 seconds.
- Inventory before cleanup: 37 controllers, 12 services, 16 requests, 1 policy, 14 models, 5 commands, 2 PHP view components, 20 Blade components, 108 Blade views, 2 JavaScript source files, 1 CSS source file, and 44 test files.

No migration, seeder, package installation/update, schema change, or development data mutation was performed.

## 2. Runtime / Build

- Laravel 13.7.0, PHP 8.3.16, Composer 2.8.5, Vite 8.0.10.
- Database driver: MySQL. Test query measurements used the isolated testing database.
- Baseline build: PASS, 56 modules; CSS 37.22 kB (gzip 7.17 kB), JS 86.49 kB (gzip 31.55 kB).
- Baseline warning: Vite plugin timing (`laravel` 73%, `vite:css` 26%); harmless diagnostic warning.
- Final build: PASS, 56 modules; CSS 28.47 kB (gzip 5.58 kB), JS 86.49 kB (gzip 31.55 kB), no warning in the final run.
- The 8.75 kB CSS reduction comes from removing the unused default Laravel welcome view from Tailwind's scanned source set. No visual CSS was removed from active pages.

## 3. Query Audit

Measurements count only `SELECT` statements through a scoped `DB::listen()` listener. The fixture contains 25 employees, 20 events, 5 documents, an active QR token, one participant, and one scanner attendance. Query logging is test-only and is not enabled globally.

| Path | Before | After | Result |
|---|---:|---:|---|
| Data Pegawai | 12 | 7 | KPI counts consolidated; unnecessary relation removed |
| Verifikasi Pegawai | 12 | 7 | aggregate counts consolidated; documents use `withCount` |
| Admin Dashboard | 10 | 6 | employee/event KPI aggregates consolidated |
| Data Kegiatan | 7 | 4 | event status counts consolidated |
| Laporan Pegawai | 12 | 8 | aggregate counts consolidated; NUP validity moved to SQL |
| Laporan Kegiatan | 7 | 5 | average attendance calculated in SQL |
| Dashboard Panitia | 2 | 1 | database query removed from Blade |
| Scanner scan mutation | 8 | 8 | already bounded; unchanged intentionally |
| Attendance list | 18 | 14 | attendance map scoped to current participant page |
| Dokumen Pegawai | 3 | 2 | duplicate document relation query removed |
| Beranda Pegawai | Not measured before | 7 | bounded after audit |
| Profil Pegawai | Not measured before | 8 | bounded after audit |
| Kegiatan Pegawai | Not measured before | 5 | attendance history now paginated |

The permanent regression test uses slightly higher ceilings than the measured values so a framework-level query addition does not make it brittle, while a row-linear N+1 still fails.

## 4. N+1 Findings

No significant relationship N+1 remained on the measured core routes. Employee, verification, event, participant, attendance, and report list queries eager-load only relations rendered by their views. The main problems were repeated aggregate queries and over-broad collection loading rather than a classic query-per-row loop.

The global header previously resolved `user.employee` for every role. It now resolves that relationship only for `pegawai`, the only role whose avatar uses the employee photo.

## 5. Dashboard

`EmployeeMetricsService` and `EventMetricsService` use one conditional aggregate query each for reusable KPI groups. Dashboard chart payloads remain limited to labels and numeric series; no full employee model or personal data is sent to JavaScript. Existing group-by queries for unit and employee composition remain database-side.

## 6. Employee

- Data Pegawai remains paginated at 15 rows.
- List queries do not load family, education, certification, documents, or QR history.
- `Employee::scopeWithValidEmployeeNumber()` applies 10-digit validation in SQL for MySQL and SQLite, replacing full-table hydration and PHP filtering.
- NIK exact search remains the Stage 3 HMAC blind-index lookup. No decryption or PHP filtering was introduced.

## 7. Profile

Profile controllers and the completion service were reviewed. Each wizard step uses the employee ownership context and the relations required by that step/progress calculation. The measured profile show path is bounded at 8 SELECT queries. No permanent profile-progress cache was introduced. Employee attendance history, previously unbounded, now uses 20-row pagination with its own `history_page` parameter.

## 8. QR / Attendance

- QR resolution still performs indexed token-hash lookup and does not decrypt or enumerate all tokens.
- Scanner creation remains in `EventAttendanceService`; transaction and duplicate handling were not widened or weakened.
- Scanner scan remains 8 SELECT queries. No cache or debounce was added because the path was already bounded.
- Duplicate attendance summary code was removed from the controller and centralized in `EventAttendanceSummaryService`.
- Attendance maps load only the employees on the current participant page and eager-load only `scanner`, not an unused `employee` relation.

## 9. Events / Reports

- Event status KPIs now use one conditional aggregate query.
- Participant generation and manual options filter valid NUP values in SQL and pluck IDs instead of hydrating employee models for PHP filtering.
- Report attendance percentage uses an aggregate subquery instead of hydrating all events and averaging model collections.
- Report filters are applied before pagination/export. Attendance report maps are scoped to exported participants.
- Historical `scan_method = barcode` remains supported for reports; no active Code 128 renderer/scanner code exists.

## 10. Import / Export

Employee import already preloads institution and position lookup maps for a request, validates a maximum 5 MB file and 1,000 data rows, and uses a transaction for the prepared mutation set. Exact employee/user conflict checks still run per prepared row to preserve current integrity behavior. A bulk conflict-query redesign is deferred because the request is bounded and changing conflict semantics would exceed this stage.

Report exports avoid relations not included in output. The custom XLSX writer still builds worksheet XML in memory; streaming/chunk redesign is deferred until a measured export-size problem exists. No sensitive fields were added to import or export.

## 11. Controller / Service Quality

- Largest audited controllers: Event (307 lines), Employee (298), Report (296 before changes), Event Attendance (290 before changes), Document (207), and Verification (206).
- Controllers were not split by line count alone.
- Reusable employee/event KPI aggregates moved to purpose-specific services.
- Attendance summary and map preparation now have one source of truth.
- Existing services remain the sources of truth for NUP transition, QR lifecycle, attendance creation, NIK lookup, document storage/access, and import.
- Final inventory: 36 controllers and 14 services. One unreferenced Breeze registration controller was deleted.

## 12. Blade / View Quality

- All 108 baseline Blade files were included in reference/query scans; 48 active route/controller entry views had already been mapped in Stage 5.
- Final Blade count is 106 after deleting two confirmed-unused entry views.
- Database query calls in Blade: 1 before, 0 after.
- `scanner/dashboard.blade.php` now receives active events from `DashboardController::panitia()`.
- Active `layouts.app` and its Breeze profile components were retained because `/profile` still uses them.
- `id-cards/pdf.blade.php` was retained because PDF is a deferred product decision, not dead-code cleanup for this stage.

## 13. CSS / JS

- Active custom CSS remains `public/assets/css/yapista-ui.css` (29,988 bytes, 1,338 lines); no global Mantis selector was changed.
- 19 views still contain inline style attributes, primarily dynamic widths/print/presentation values. No mass rewrite was justified.
- JavaScript source remains `resources/js/app.js` and `resources/js/bootstrap.js`; Alpine, Bootstrap, and Axios are actively used.
- Fourteen Blade files contain page/component scripts. Dashboard chart initialization checks target elements and scanner listeners remain page-local.
- No `console.log`, duplicate global scanner listener, or null-target chart error was found.

## 14. Assets

- Main logo remains `public/assets/images/logo-yapista-hris.png`.
- ApexCharts 3.44.0 is local and loaded only by the dashboard view.
- Large files under `public/assets` are the bundled Mantis distribution (icon fonts, editor/pdfmake plugins, and landing assets). Two identical landing image pairs were identified, but removal was deferred because template asset references can be dynamic.
- Public Sans is actively used by Mantis and remains an external Google Fonts request with local sans-serif fallback. Typography was not changed.
- No user photo/document, uploaded file, screenshot, or benchmark artifact was added or removed.

## 15. Dependencies

- `chillerlan/php-qrcode`, `phpoffice/phpspreadsheet`, Laravel Breeze, and all direct Composer packages have active code/test use or framework development use; none was removed.
- Tailwind remains required by the active generic account layout; Alpine and Axios remain imported by Vite sources.
- `npm ls --depth=0` reports five local extraneous packages: `@emnapi/core`, `@emnapi/runtime`, `@emnapi/wasi-threads`, `@napi-rs/wasm-runtime`, and `@tybys/wasm-util`.
- They are not declared in `package.json`, are not committed, and are treated as local `node_modules` residue rather than a source defect.
- PHPStan/Larastan/Psalm and ESLint are not installed, so no new static-analysis/lint package was added.

## 16. Dead / Legacy Code

Deleted after route/controller/reference proof:

- `app/Http/Controllers/Auth/RegisteredUserController.php`;
- `resources/views/auth/register.blade.php`;
- `resources/views/welcome.blade.php`.

Public registration compatibility routes still redirect to login and invitation registration remains active. No `dd`, `dump`, `var_dump`, `print_r`, `ray`, `console.log`, `TODO`, `FIXME`, `HACK`, or `TEMP` marker was found in active project source. Legacy `nup` and `foundation_registry_number` references remain only in migrations and regression tests. Barcode references remain only for historical attendance compatibility.

## 17. Tests

- New: `PerformanceQueryAuditTest`, using isolated synthetic data and scoped SELECT counting.
- Updated: `EmployeeNumberConsistencyTest` now verifies a 10-character non-digit value is invalid in the database-level NUP scope.
- Targeted broad regression: 153 passed, 1,258 assertions, 0 failed.
- Isolated performance test: 1 passed, 39 assertions.
- Final full suite: 274 passed, 2,211 assertions, 0 failed, 0 skipped; duration 24.054 seconds.
- Laravel Pint on touched files: PASS.
- Blade view cache: PASS.
- Final `config:clear`, `route:clear`, `view:clear`, and `event:clear`: PASS. The database-backed `cache:clear` sub-step and a final repeat of `migrate:status` could not connect because the local MySQL service stopped accepting connections after browser QA. The earlier Stage 6 migration check completed at 26 Ran / 0 Pending; no migration or data mutation was attempted.

One interrupted test command caused two temporary missing-fixture failures while its storage cleanup overlapped the next run. `EmployeeDocumentSecurityTest` immediately passed 13/13 in isolation, and a clean full-suite rerun passed 274/274. This was a test-process overlap, not an application regression.

## 18. Performance Findings

| ID | Severity | Area | Before | Finding | Fix | After | Status |
|---|---|---|---|---|---|---|---|
| PERF-001 | MEDIUM | Employee/verification KPI | 4 separate counts per page | Repeated full-table aggregate round trips | Conditional aggregate service | 1 metrics query | FIXED |
| PERF-002 | MEDIUM | Dashboard/events | 10/7 route queries | Repeated employee/event status counts | Shared aggregate services | 6/4 queries | FIXED |
| PERF-003 | MEDIUM | Reports | Full event hydration and PHP average; all employee hydration for NUP status | Excess data and memory | SQL aggregate/subquery and model scope | Employee 8, event 5 queries | FIXED |
| PERF-004 | MEDIUM | Attendance | Attendance map for every active participant | Pagination did not bound related attendance data | Scope map to current/export participant IDs | 14 route queries | FIXED |
| PERF-005 | LOW | Documents/header | Duplicate document load; employee header lookup for all roles | Unnecessary queries | Remove duplicate load and role-gate header relation | Documents 2 queries | FIXED |
| PERF-006 | MEDIUM | Employee history | Unbounded attendance `get()` | History could grow without limit | Paginate 20 | 5 route queries | FIXED |
| PERF-007 | INFO | QR scanner | 8 SELECT queries | Path already bounded/indexed | No performance change | 8 SELECT queries | VERIFIED |
| PERF-008 | LOW | Import/export | Bounded per-row conflict checks and in-memory XLSX | Potential future scale limit, not demonstrated at current cap | No risky redesign | Unchanged | DEFERRED |

No HIGH performance finding remained.

## 19. Code Quality Findings

| ID | Severity | Area | Finding | Fix | Verification | Status |
|---|---|---|---|---|---|---|
| CQ-001 | HIGH | Scanner Blade | View executed an Eloquent query | Move preparation to controller | Blade DB-query scan = 0; route query 2 to 1 | FIXED |
| CQ-002 | MEDIUM | Attendance | Controller duplicated summary/map logic | Reuse `EventAttendanceSummaryService` | Attendance/report tests and full suite | FIXED |
| CQ-003 | MEDIUM | Metrics | Same aggregate definitions repeated across controllers/services | Add focused employee/event metrics services | Query test and dashboard/report tests | FIXED |
| CQ-004 | LOW | Legacy Breeze | Three unreferenced registration/welcome artifacts | Delete only files proven unreferenced | Auth/profile targeted tests | FIXED |
| CQ-005 | LOW | Debug/markers | No active debug or TODO marker found | No change | Static scan | VERIFIED |
| CQ-006 | INFO | Legacy identifiers | NUP fields only migrations/tests; barcode only historical method support | Preserve compatibility | Static scan and regression suite | VERIFIED |
| CQ-007 | LOW | Mantis assets/fonts | Large bundled assets and external font remain | Avoid unsafe template purge/visual change | Build and browser QA | DEFERRED |

## 20. Before / After

- Core measured SELECT queries across the ten baseline paths: 91 before, 62 after (29 fewer, 31.9% reduction). This total excludes the three newly measured employee paths that had no before measurement.
- View database queries: 1 to 0.
- CSS bundle: 37.22 kB to 28.47 kB; JS unchanged at 86.49 kB.
- Tests: 273/2,172 to 274/2,211, with zero failures/skips in the final run.
- Browser smoke QA passed without console errors or horizontal overflow on desktop Admin (Dashboard, Employees, Employee Detail, Reports), mobile 390 px Employee (Home, Activities, E-Card, Documents, Account), and Panitia (Scanner, Attendance).

## 21. Deferred Cleanup

1. Bulk import conflict-query redesign is deferred until a measured issue exists beyond the current 1,000-row cap.
2. XLSX streaming/chunk redesign is deferred until export volume or memory measurements justify it.
3. Mantis distribution asset pruning and duplicate landing-image removal require a dedicated asset-reference manifest; dynamic template references make deletion unsafe now.
4. Public Sans remains externally loaded; self-hosting is a deployment/offline-font decision for Stage 8.
5. Active generic account pages still depend on Breeze/Tailwind layout/components; replacing them would be a presentation redesign.
6. E-Card PDF remains a product feature decision and was not implemented.
7. PHP static analysis and JavaScript lint tooling are unavailable and were not installed in this stage.
8. Local MySQL must be started again before the next development browser/database check; this is an environment-service caveat, not a test/build regression.

Stage 6 is complete and the codebase is ready to proceed to Stage 7 Regression & Edge Case Testing after the local MySQL service is available again and this diff is reviewed. Stage 7 was not started.
