# YAPISTA HRIS Release Candidate & Go-Live

Audit date: 16 Agustus 2026 (Asia/Jakarta)

Mode: **B - PRE-GO-LIVE INFRASTRUCTURE VERIFICATION ONLY**. Production host, database, DNS, TLS, SMTP, or real employee data were not changed. Production-changing execution is not authorized.

## 1. Stage 9 Gate

Stage 9 gate: **PASS**.

- Authorized operator gave `HUMAN UAT ACCEPTANCE: APPROVED` on 16 Agustus 2026.
- Codex-assisted application P0: 31/31 executed; 28 PASS; 3 PASS WITH NOTE; 0 FAIL; 0 application BLOCKED.
- Open application severity: BLOCKER 0, CRITICAL 0, HIGH 0.
- Double post-fix regression, build, migration, dependency security, and restore drill are green.
- Physical scanner, external SMTP, hosting/domain/TLS, and monitoring remain documented infrastructure actions.

## 2. Release Candidate

| Item | Actual |
|---|---|
| Branch | `main` |
| Pre-release/application source HEAD | `9b7efda53a87fbe4f9929ad2793d552cf3344248` |
| Release Candidate SHA | `9b7efda53a87fbe4f9929ad2793d552cf3344248` |
| Superseded application RC | `66b29b2f668aa2ed60d2e5b7f9e8d622943d18b8` (legacy QR compatibility); earlier `ae40647d9dbcc6a43f5e3460813b786bef5032ac` |
| Version | `v1.0.0` |
| Working tree before release docs | CLEAN |
| Feature/dependency freeze | ACTIVE |

The controlled legacy QR compatibility change invalidated the previous deployment RC. Production source of truth is now the exact new RC SHA above. Documentation commits created after validation do not change application source.

### Controlled Legacy QR Compatibility

- Secure QR remains the primary standard: `YAPISTA:EMPLOYEE:<random-token>` with active/revoked token validation unchanged.
- Attendance scanner additionally accepts an exact 10-digit `employees.employee_number` for old physical cards only.
- Both formats enter the same attendance service and retain active employee, participant, authorization, and event+employee duplicate protections.
- Compatibility is controlled through cached config `attendance.allow_legacy_nup_qr`, backed by `ATTENDANCE_ALLOW_LEGACY_NUP_QR=true` for the transition.
- No login, document, profile, generic employee lookup, E-Card payload, migration, or dependency behavior was changed.
- Legacy NUP QR is predictable and transitional; new cards remain secure-token-only and the fallback should be retired after old cards are replaced.

### Complete Admin/HR Employee Detail

- Admin/HR Employee Detail now presents the complete authorized employee master profile across personal, employment, contact/address, emergency contact, family, education, certification, administration/bank/BPJS, document, verification, and profile-completion sections.
- The audit found 62 profile inputs across 26 employee scalar fields and the related family, education, certification, administrative, and document records. Existing relationships and `EmployeeProfileProgressService` remain the source of truth; no migration or business rule changed.
- NUP remains fully visible. NIK, family-card number, family NIK, certificate numbers, bank account, tax identity, and BPJS numbers are masked. Raw QR token/hash, ciphertext, and private storage paths are not rendered.
- Browser QA with a Super Admin session passed for incomplete and complete UAT employees at 1440x900 and 390x844: no page overflow, broken layout, or application console error. HR access and Pegawai/Panitia/guest denial are covered by feature tests.

## 3. Final Test Results

| Gate | Result |
|---|---|
| Full suite #1 | PASS; 310 tests; 2.606 assertions; 0 failed; 0 skipped; 119.489 s |
| Full suite #2 | PASS; 310 tests; 2.606 assertions; 0 failed; 0 skipped; 25.633 s |
| Frontend build | PASS; Vite 8.0.16; 57 modules; 3,68 detik |
| CSS | 28,47 kB; gzip 5,58 kB |
| JS | 89,97 kB; gzip 32,66 kB |
| Build warning | Plugin timing informational: Laravel 69%, CSS 30%; no asset/build error |
| Composer platform requirements | PASS, non-dev |
| Config/route/view cache preflight | PASS; local cache cleared after verification |

## 4. Dependency Security

- `composer audit --locked --no-dev`: 0 advisory.
- `npm audit`: 0 vulnerability.
- `npm audit --omit=dev`: 0 vulnerability.
- No dependency update was performed in Stage 10.
- `composer.lock` and `package-lock.json` remain tracked sources of truth.
- Five npm packages appear `extraneous` in the local `node_modules`; they are not in the lockfile release contract and no vulnerability is reported. Production must use `npm ci`.

## 5. Database

Candidate environment: MySQL 8.4.3. Migration: **26 Ran, 0 Pending**.

The latest migration adds unique/restrict constraints and has explicit precondition checks. It is forward-safe when the integrity dry-run is clean; production must run the dry-run before migration and stop if duplicate or orphaned relationships are reported.

Final read-only results:

| Check | Count |
|---|---:|
| Duplicate NUP / invalid non-null NUP | 0 / 0 |
| Legacy NUP/foundation rows | 0 / 0 |
| Duplicate participant / attendance groups | 0 / 0 |
| Multiple active QR groups | 0 |
| Ineligible active QR / eligible missing QR | 0 / 0 |
| Valid NUP not verified / verified missing timestamp | 0 / 0 |
| Position-unit mismatch | 0 |
| NIK verification issue | 0 |

NUP source remains `employees.employee_number`; leading zero is preserved by string storage. NIK remains encrypted plus HMAC blind index. No key rotation occurred. QR remains random/revocable and does not use NUP/NIK as payload.

## 6. Backup & Restore

- Stage 9.5 MySQL backup and isolated restore drill: PASS.
- Drill dump size: 61.165 bytes.
- Drill SHA-256: `49687b5ee8d304c463af3c24fabb8f437c268b0b2487f2234bc2196a5463cd9c`.
- Restored aggregate counts, 26 migration state, NIK decrypt/HMAC, and QR resolution: PASS.
- Private file backup strategy: AVAILABLE and documented in `docs/deployment/backup-restore-runbook.md`.
- Production database/private-file backup: NOT EXECUTED; required immediately before production mutation.
- No schema or encryption architecture change occurred after the restore drill.

## 7. Production Environment

Values below are status only; no secret is printed.

| Requirement | Candidate local | Production status |
|---|---|---|
| `APP_ENV` | local | PENDING (`production` required) |
| `APP_DEBUG` | true | PENDING (`false` required) |
| `APP_URL` | localhost | PENDING (final HTTPS URL required) |
| `APP_TIMEZONE` | config default Asia/Jakarta | CONFIGURED requirement documented |
| `APP_KEY` | CONFIGURED local; ignored by Git | PENDING production secret + escrow |
| MySQL host/database/user/password | CONFIGURED local | PENDING production target/credential |
| Session database/secure cookie | database / unset local | PENDING secure cookie over HTTPS |
| Cache / queue | database / database | CONFIGURED design; queue worker N/A currently |
| Filesystem | local private | CONFIGURED design; production permissions pending |
| `EMPLOYEE_NIK_LOOKUP_KEY` | CONFIGURED local; ignored by Git | PENDING separate production secret + escrow |
| Mail | log | PENDING external SMTP if operationally required |

The `.env.example` is not a production credential template; `docs/deployment/production-environment.md` is the required variable matrix. Production secrets must be injected outside Git.

## 8. Infrastructure

| Item | Status | Required action |
|---|---|---|
| Hosting/server | ACTION REQUIRED | Supply host, service account, app path, and compatible runtime |
| Domain/DNS | ACTION REQUIRED | Supply final domain and records |
| HTTPS/TLS | ACTION REQUIRED | Activate valid certificate and HTTPS redirect |
| MySQL production | ACTION REQUIRED | Supply DB and least-privilege credentials |
| SMTP | ACTION REQUIRED if invitation/reset used | Controlled external delivery test |
| Backup destination | ACTION REQUIRED | Encrypted off-host DB/private-file destination |
| Monitoring | ACTION REQUIRED | HTTP, Laravel error, disk, and DB checks |
| Queue worker | NOT APPLICABLE | No active `ShouldQueue`/dispatch runtime dependency found |
| Scheduler | NOT APPLICABLE | No active scheduled business task found |
| Physical QR scanner | PENDING PRE-GO-LIVE | Required before day-one scanner attendance; application HID flow PASS |

### Pre-Go-Live Matrix

| Area | Status | Evidence / missing input |
|---|---|---|
| Release SHA | READY | Exact RC exists and application tree is unchanged after RC |
| Tag | ACTION REQUIRED | Local annotated `v1.0.0` still points to superseded RC; not pushed; explicit operator approval required before retarget |
| Server | ACTION REQUIRED | Production host/service account not provided |
| App Path | ACTION REQUIRED | Production application path/document root not provided |
| Document Root | ACTION REQUIRED | Must resolve to `<application>/public`; web denial unverified |
| PHP | ACTION REQUIRED | Candidate PHP 8.3.16/platform PASS; production runtime not reachable |
| PHP Extensions | ACTION REQUIRED | Candidate requirements PASS; production `php -m` unavailable |
| Composer | ACTION REQUIRED | Production Composer 2.10.2+ or clean build artifact unavailable |
| MySQL | ACTION REQUIRED | Candidate MySQL 8.4.3 PASS; production server not provided |
| Production DB | ACTION REQUIRED | Database name/account/charset/collation not provided |
| DB User | ACTION REQUIRED | Least-privilege non-root application account unverified |
| `APP_KEY` | ACTION REQUIRED | Local key configured/ignored; production secret and escrow unverified |
| NIK Lookup Key | ACTION REQUIRED | Local key configured/ignored; separate production secret and escrow unverified |
| Secret Escrow | ACTION REQUIRED | Operator-controlled recovery storage not identified |
| Domain | ACTION REQUIRED | Final production hostname not provided |
| DNS | ACTION REQUIRED | Provider/record/status not provided |
| HTTPS/TLS | BLOCKER | Domain, certificate, HTTPS redirect, and secure-cookie evidence unavailable |
| Backup Destination | BLOCKER | Encrypted off-host production destination not provided |
| Private Storage | ACTION REQUIRED | Source design PASS; production path/permission/direct-URL denial unverified |
| Permissions | ACTION REQUIRED | Production service account and least-privilege write check unavailable |
| SMTP | ACTION REQUIRED | Day-one requirement not declared; external delivery untested |
| Monitoring | ACTION REQUIRED | Availability/log/disk/DB monitor destination not provided |
| Logging | ACTION REQUIRED | Production log path, level, rotation, and sensitive-log review unavailable |
| Disk Monitoring | ACTION REQUIRED | Thresholds/alerts for logs, uploads, DB, temp, and backup staging unavailable |
| Scanner | PENDING PRE-GO-LIVE | Application HID flow PASS; physical device untested |
| Queue | NOT APPLICABLE | No active runtime queued job found |
| Scheduler | NOT APPLICABLE | No scheduled business task found |
| Tests | READY | Post-change run twice: 310 tests, 2.606 assertions, 0 failed/skipped |
| Build | READY | Vite 8.0.16, 57 modules, PASS |
| Security Audit | READY | Composer 0; npm full/production 0 |
| Migration | READY | Candidate 26 Ran, 0 Pending; production not touched |
| Rollback | ACTION REQUIRED | Runbook/drill PASS; production target, owner, previous release, and backup are unverified |

Because mandatory production infrastructure cannot be verified, the hard Pre-Go-Live gate is not satisfied.

Overall matrix: READY 5, ACTION REQUIRED 22, BLOCKER 2, PENDING PRE-GO-LIVE 1, NOT APPLICABLE 2. Mandatory infrastructure gate: READY 0 of 13, ACTION REQUIRED 11, BLOCKER 2.

## 9. Release Tag

The local annotated tag `v1.0.0` still resolves to superseded RC `ae40647d9dbcc6a43f5e3460813b786bef5032ac`. It was not pushed. Retargeting to new RC `9b7efda53a87fbe4f9929ad2793d552cf3344248` requires explicit operator approval and must not happen silently.

## 10. Deployment Plan

The plan is fully captured in `docs/deployment/go-live-checklist.md`: verify target and secrets, take DB/private-file backup, deploy exact RC, install from lockfiles, build/promote assets, run forward migration, rebuild caches, then execute controlled role/storage/QR smoke and observation.

## 11. Deployment Execution

**NOT EXECUTED.** The required exact operator statement `GO-LIVE APPROVED` has not been provided. No production host/path/database target is available in this session.

## 12. Migration

- Candidate migration status: 26 Ran, 0 Pending.
- Production `migrate --force`: NOT EXECUTED.
- No `migrate:fresh`, refresh, rollback, wipe, or seeder command was run.

## 13. Smoke Tests

- New local public smoke: login page PASS at 1280 px and 390x844; CSRF/form/logo present; 0 broken image, horizontal overflow, or console warning/error.
- Stage 9 accepted browser evidence covers Admin/HR, Pegawai, Panitia, E-Card, document, scanner, report, and mobile 390/430.
- Targeted change-impact browser UAT: Panitia scanner page, legacy NUP success, duplicate, nonparticipant, invalid payload, manual attendance, and attendance-list consistency PASS. Secure-token resolution is PASS WITH NOTE through unchanged resolver plus targeted automated tests; fresh browser secure-token entry was blocked by isolated clipboard, not by the application.
- Production public/admin/employee/panitia/QR/SMTP/upload smoke: NOT EXECUTED.

## 14. Monitoring

Source requires no new monitoring package. Production operator must provide availability, Laravel/web error, disk, and database connectivity checks. Queue-failure monitoring becomes required only if queued jobs are introduced.

## 15. Issues

- Open application BLOCKER: 0.
- Open application CRITICAL: 0.
- Open application HIGH: 0.
- Infrastructure actions remain open and are not mislabeled as application bugs.
- Local `node_modules` has five extraneous transitive build packages; `npm ci` will recreate the lockfile-defined tree.

## 16. Rollback Readiness

- Source rollback uses an explicit known-good Git SHA, never copied folders.
- Database rollback is evaluated separately from source rollback; restore may be required.
- DB and private storage must be backed up as separate protected streams before deployment.
- Trigger conditions and exact operational checklist are documented.
- Current restore drill proves the procedure locally; production backup/restore responsibility remains with the operator.

## 17. Infrastructure Actions

Before Go-Live: provision host/path/service account, production DB, final domain/DNS/TLS, APP/lookup key secret escrow, private storage permissions, off-host backup, monitoring, and external SMTP if mail workflows launch. Validate the physical scanner before the first production attendance event or explicitly record scanner attendance as not required on day one.

After Go-Live: rotate/disable UAT credentials, retain quarantine privately pending retention decision, start a controlled observation window, then broaden rollout. Do not mass import real data or send mass invitations without separate approval.

## 18. Go-Live Decision

Release Candidate technical gate: **PASS**.

Deployment mode: **MODE B - PRE-GO-LIVE INFRASTRUCTURE VERIFICATION ONLY**.

Production deployment authorization: **NOT PROVIDED**. Mandatory infrastructure target details are pending, including production server/path/database, TLS, secrets, off-host backup, and monitoring. No production-changing action is permitted.

## 19. Post-Go-Live Observation

NOT STARTED. Planned checks: login success, HTTP/Laravel errors, DB connectivity, private storage, disk, mail when enabled, and QR attendance when operational. Wider rollout follows controlled Admin/HR, employee, and Panitia checks.

## 20. Final Status

The new `v1.0.0` application candidate is technically validated and reproducible at `9b7efda53a87fbe4f9929ad2793d552cf3344248`; RC `66b29b2f668aa2ed60d2e5b7f9e8d622943d18b8` is superseded for deployment. The unpushed local tag still resolves to the earlier RC `ae40647d9dbcc6a43f5e3460813b786bef5032ac` pending explicit operator approval. Production remains untouched. Deployment cannot proceed because mandatory infrastructure has not been supplied or verified and explicit Go-Live approval has not been given.

**GO-LIVE BLOCKED - INFRASTRUCTURE ACTION REQUIRED**
