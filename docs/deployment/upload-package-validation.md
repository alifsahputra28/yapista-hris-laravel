# Prebuilt Upload Package Validation

Preparation: 31 August 2026. Version: v1.0.0.

## Source Changes

The end-of-month dashboard failure was reproduced with August 31, leap-day
February 29 and December 31 fixtures. SQLite Eloquent date casts store midnight
timestamps; an inclusive upper bound consisting of only YYYY-MM-DD omitted the
last day. Exact equality also missed active-today timestamp values. Event metrics
now use half-open month/day ranges, supporting native MySQL DATE and timestamp
representations without changing attendance eligibility or aggregation rules.
All three boundary cases failed before the fix and pass afterwards.

The previously uncommitted scanner operational layout and dependent Unit/Jabatan
form changes are preserved as part of the reviewed source. Their automated tests
remain included; no older release tag is substituted for current source.

## Assembly

Run from a clean, reviewed source commit after both full regressions and security
audits pass. Build assets with npm ci followed by npm run build. Then:

```powershell
pwsh -File scripts/build-upload-package.ps1 -OutputDirectory D:\Deploy\yapista-hris-v1.0.0
```

The builder refuses a dirty repository, a pre-existing output directory, or an
output path inside the source tree. It archives only runtime paths from exact
HEAD and adds the verified build output. It never copies local storage, .env,
public/hot, public/storage, Git, node_modules, tests, docs or local cache from the
workspace. It installs production vendor from composer.lock in the artifact,
not in the development source. No package upgrades are performed.

Before distribution, verify in an isolated temporary environment, remove only
generated artifact caches/logs, scan artifact/ZIP entries for excluded paths,
check manifest asset references, and emit RELEASE-MANIFEST.txt plus a ZIP checksum
sidecar. The ZIP checksum must remain external to the ZIP to avoid self-reference.
Package file count/bytes are recorded in the internal manifest excluding the
manifest's own bytes; exact full totals are recorded in the external report.

The same source supports staging with explicit guarded UAT seeding. Literal
synthetic email/NUP definitions are allowed in guarded seeder SOURCE, never as
runtime accounts, passwords, uploaded files or database dumps in the package.

## Release Safety

This artifact is not production deployment approval. Existing local tag v1.0.0
still points to an older RC and is not used as the artifact source. Retargeting
requires separate operator approval; no tag is changed or pushed here.

Prior failure counts in earlier UAT/data hygiene reports are historical evidence.
Current package verification results will be appended after artifact checks.
Source changes after the recorded Application SHA make the package stale.
