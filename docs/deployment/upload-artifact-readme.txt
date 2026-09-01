YAPISTA HRIS - PREBUILT UPLOAD ARTIFACT

Source identity and validation results: RELEASE-MANIFEST.txt.
This folder includes locked production Composer dependencies and compiled Vite
assets. Composer/npm are not required on the hosting server for this artifact.
Do not edit PHP/Blade here: fix source, test, commit and rebuild instead.

BEFORE UPLOAD
1. Verify the archive SHA-256 using its separate .sha256 sidecar.
2. Confirm server PHP satisfies composer.json and the listed required extensions.
   vendor/composer/platform_check.php checks part of this at bootstrap. It does
   not replace verification of all extensions on the hosting PHP runtime.
3. Arrange database, HTTPS/TLS, private storage, backup/restore, admin provisioning
   and a rollback plan. Production deployment requires separate operator approval.

AFTER APPROVED UPLOAD
1. Upload the folder contents. Set document root to <application>/public.
   Never expose the repository/application root as public_html.
   If hosting cannot set document root: HOSTING REQUIRES SPECIAL PUBLIC_HTML
   MAPPING. Obtain provider-specific instructions; do not improvise index.php moves.
2. Create .env securely on the server from the safe .env.example template.
   APP_ENV=production, APP_DEBUG=false, APP_URL=https://actual-domain,
   APP_TIMEZONE=Asia/Jakarta, SESSION_DRIVER=database,
   SESSION_SECURE_COOKIE=true, CACHE_STORE=database.
   Configure MySQL database/account and all required environment-specific values.
3. Supply APP_KEY and separate EMPLOYEE_NIK_LOOKUP_KEY through secure storage.
   Back up both keys securely. NEVER regenerate/rotate keys for existing encrypted
   data. Configure SMTP when invitation/password reset is required; no default
   production account or password is provided. UAT_SEED_PASSWORD must be absent
   from production. Do not copy local .env or runtime data.
4. Make storage/ and bootstrap/cache/ writable by the PHP service account using
   least privilege, not chmod 777. Keep private uploads outside public paths.
   public/storage is intentionally absent. Only link the PUBLIC disk if needed;
   never link private storage or quarantine.
5. Back up an existing target DB and required private files, then verify checksums.
   Run php artisan migrate --force via approved CLI/deployment mechanism.
   Migration is NOT performed by uploading files. No web migration/debug endpoint
   is included. If CLI is unavailable, obtain a provider-approved mechanism first.
6. Optional, after reviewing organization masters: php artisan db:seed --force.
   Default seed creates only Unit/Jabatan. Never run UatSeeder, DevelopmentSeeder,
   factories, migrate:fresh, migrate:refresh, db:wipe or wildcard cleanup in production.
7. With the correct target .env: php artisan optimize:clear, then
   php artisan config:cache, php artisan route:cache, php artisan view:cache.
8. Smoke test HTTPS login/assets, authorized admin/employee/scanner pages,
   private-file access denial, logs and database integrity before opening traffic.
   SMTP delivery and physical scanner testing remain operator responsibilities.

STAGING
Use a separate, empty staging DB and private storage, APP_ENV=staging/uat.
Run normal migrations, master seed, then explicitly:
php artisan db:seed --class=UatSeeder --force
New synthetic accounts require operator-supplied UAT_SEED_PASSWORD (12+ characters)
through a secret store/process environment, never a CLI argument or tracked file.
Remove the seeding secret/rebuild config cache after provisioning. Synthetic emails
and NUP definitions in guarded seed source are not provisioned accounts/passwords.
Use synthetic documents only. Never clone the local database/default accounts.

UPLOAD ALONE DOES NOT PROVIDE
.env, secrets, database creation, migrations, permissions, document root, TLS,
production admin provisioning, monitoring or a valid backup/rollback plan.

ARCHIVE CHECKSUM
The ZIP SHA-256 is in its external .sha256 sidecar and source deployment report.
It cannot be embedded in the same ZIP without changing the checksum itself.
