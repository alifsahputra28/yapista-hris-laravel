# YAPISTA HRIS v1.0.0 Go-Live Checklist

Release candidate source: `ae40647d9dbcc6a43f5e3460813b786bef5032ac`

Status: **PRE-GO-LIVE VERIFIED; INFRASTRUCTURE ACTION REQUIRED; PRODUCTION EXECUTION NOT AUTHORIZED**

Never record credentials, private paths, employee PII, or raw QR tokens in this checklist.

## Pre-Deploy

### Application Gate

- [x] Stage 9 operator acceptance recorded.
- [x] Release Candidate SHA confirmed.
- [x] Local annotated tag `v1.0.0` targets the exact RC SHA; tag is not pushed.
- [x] Application source working tree clean at validation.
- [x] Full automated suite PASS twice: 299 tests / 2.489 assertions each.
- [x] Frontend build PASS with Vite 8.0.16.
- [x] Composer production audit clean.
- [x] npm full and production audits clean.
- [x] Migration reviewed: 26 Ran / 0 Pending on candidate environment.
- [x] Read-only integrity audit reports 0 unexpected anomaly.
- [x] Backup/isolated restore drill evidence PASS.
- [x] Rollback source identified: previous known-good `3dc7391`; UAT fix `beb8f70` retained in RC.
- [ ] Exact production target and application path confirmed by operator.
- [ ] Operator states `GO-LIVE APPROVED`.

### Infrastructure Gate

- [ ] Production server and PHP 8.3+ runtime ready.
- [ ] Document root points to `<release>/public`.
- [ ] Production MySQL 8.x database and least-privilege account ready.
- [ ] Production `APP_URL` uses final HTTPS URL.
- [ ] Domain/DNS and valid TLS certificate ready.
- [ ] `APP_ENV=production` and `APP_DEBUG=false` confirmed without printing values.
- [ ] Stable production `APP_KEY` available in secret store and recovery escrow.
- [ ] Stable, separate `EMPLOYEE_NIK_LOOKUP_KEY` available in secret store and recovery escrow.
- [ ] `SESSION_SECURE_COOKIE=true` confirmed over HTTPS.
- [ ] `storage/` and `bootstrap/cache/` writable by service account without `0777`.
- [ ] Private upload storage is outside document root and not publicly linked.
- [ ] Encrypted off-host database/private-file backup destination ready.
- [ ] RPO/RTO and retention accepted by system owner.
- [ ] SMTP controlled delivery PASS if invitation/password reset is required at launch.
- [ ] Monitoring covers HTTP availability, Laravel errors, disk, and DB connectivity.
- [ ] Physical QR scanner PASS, or explicitly recorded as not required on day one.
- [ ] UAT/temporary credentials scheduled for rotation or deactivation.

### Operator Input Package

- [ ] Hosting/provider, OS, web server, SSH/panel access method, and service account supplied.
- [ ] Production application path, deployment method, and document root supplied.
- [ ] Production PHP/Composer/MySQL command evidence supplied.
- [ ] Production database name, charset/collation, and least-privilege account confirmed without exposing password.
- [ ] Domain, DNS provider/status, certificate, and HTTP-to-HTTPS strategy supplied.
- [ ] Production `APP_KEY`, NIK lookup key, DB credential, and optional SMTP credential marked configured in secure escrow.
- [ ] Private-storage path, ownership, write permission, and direct-public-denial evidence supplied.
- [ ] Encrypted off-host backup destination, access, retention, and restore owner supplied.
- [ ] Day-one SMTP and physical-scanner requirement explicitly decided.
- [ ] Monitoring/log rotation/disk threshold method and responsible operator supplied.
- [ ] Production rollback owner, previous release state, and maintenance window supplied.

### Backup Immediately Before Mutation

- [ ] Maintenance window/operator recorded.
- [ ] Database dump created with `mysqldump --single-transaction`.
- [ ] Database dump timestamp, byte size, and SHA-256 verification recorded outside Git.
- [ ] `storage/app/private` backup created and checksum verified.
- [ ] Backup stored outside `public/` and outside release repository.
- [ ] Restore/rollback operator confirms backup is reachable.

Do not run migration until every required pre-deploy and backup item is checked.

## Deploy

- [ ] Checkout/deploy exact RC SHA; do not copy an untracked working directory.
- [ ] Install backend dependencies using `composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction`.
- [ ] Install frontend dependencies using `npm ci` on build host.
- [ ] Run `npm run build` or promote the verified prebuilt artifact.
- [ ] Configure production environment outside Git; do not generate/rotate encryption keys.
- [ ] Enable maintenance mode only immediately before write-impacting deployment work.
- [ ] Run `php artisan migrate --force` only after verified backup.
- [ ] Capture migration output; stop on any error without destructive improvisation.
- [ ] Run `php artisan optimize:clear`.
- [ ] Run `php artisan config:cache`.
- [ ] Run `php artisan route:cache`.
- [ ] Run `php artisan view:cache`.
- [ ] Queue restart is NOT APPLICABLE for v1.0.0 unless a worker is introduced before deployment.
- [ ] Scheduler setup is NOT APPLICABLE; no active scheduled business task exists.
- [ ] Exit maintenance only after bootstrap, migration, and cache checks pass.

## Post-Deploy

- [ ] Public `/` and `/login` return without HTTP 500 over HTTPS.
- [ ] Logo/build assets load with no mixed content or broken image.
- [ ] CSRF token is present on login.
- [ ] Controlled Super Admin login/dashboard/employee/detail/verification/event/report smoke PASS.
- [ ] Controlled Pegawai login/home/activity/E-Card/document/account smoke PASS.
- [ ] Controlled Panitia login/scanner/attendance list smoke PASS.
- [ ] Synthetic QR scan PASS only on an approved test event.
- [ ] Duplicate synthetic QR is rejected without a second attendance row.
- [ ] Controlled SMTP delivery PASS if mail is enabled.
- [ ] Optional dummy private upload/access/denial/cleanup PASS.
- [ ] Laravel and web-server logs show no unexpected ERROR/CRITICAL/SQLSTATE/permission failure.
- [ ] Post-deploy migration status has 0 Pending.
- [ ] Post-deploy aggregate integrity checks report 0 unexpected anomaly.
- [ ] Public employee sensitive file exposure remains 0.
- [ ] Observation window started before wider rollout.

## Rollback

Trigger rollback/fail-safe for global login failure, migration/data corruption, private document loss, core QR attendance failure, repeated HTTP 500, security exposure, or critical integrity anomaly.

- [ ] Incident owner and rollback decision recorded.
- [ ] Write traffic stopped/maintenance enabled when needed.
- [ ] Failed-state backup captured if safe.
- [ ] Source returned to an explicitly recorded known-good SHA.
- [ ] Database restored when schema/data compatibility requires it; code rollback alone is not treated as database rollback.
- [ ] Private storage restored from the matching protected backup when required.
- [ ] Cache rebuilt against the restored source/environment.
- [ ] Public/admin/employee/panitia smoke repeated.
- [ ] Integrity aggregate and logs reviewed before traffic reopens.

Production deployment remains prohibited until operator approval and all mandatory infrastructure items are satisfied.
