param(
    [Parameter(Mandatory = $true)][string]$OutputDirectory,
    [string]$Version = 'v1.0.0',
    [string]$ApplicationReleaseSha
)

$ErrorActionPreference = 'Stop'
$source = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
$output = [IO.Path]::GetFullPath($OutputDirectory)
if ($output.StartsWith($source + [IO.Path]::DirectorySeparatorChar, [StringComparison]::OrdinalIgnoreCase) -or $output -eq $source) {
    throw 'Artifact must be outside the source repository.'
}
if (Test-Path -LiteralPath $output) { throw 'Output already exists; do not overwrite a release.' }
Push-Location $source
try {
    if (@(git status --porcelain).Count) { throw 'Commit reviewed source/documentation before packaging.' }
    $packagingSha = (git rev-parse HEAD).Trim()
    if ($LASTEXITCODE -ne 0) { throw 'Cannot determine source SHA.' }
    if ([string]::IsNullOrWhiteSpace($ApplicationReleaseSha)) { $ApplicationReleaseSha = $packagingSha }
    $applicationSha = (git rev-parse "$ApplicationReleaseSha`^{commit}").Trim()
    if ($LASTEXITCODE -ne 0) { throw 'Cannot resolve application release SHA.' }
    $build = Join-Path $source 'public/build'
    if (!(Test-Path -LiteralPath (Join-Path $build 'manifest.json'))) { throw 'Run npm ci and npm run build first.' }
    $temporary = Join-Path ([IO.Path]::GetTempPath()) ('yapista-source-' + [guid]::NewGuid().ToString('N'))
    New-Item -ItemType Directory -Path $temporary | Out-Null
    $sourceArchive = Join-Path $temporary 'source.zip'
    $paths = @('app', 'bootstrap', 'config', 'database', 'public', 'resources', 'routes', 'artisan', 'composer.json', 'composer.lock', '.env.example')
    if (Test-Path -LiteralPath (Join-Path $source 'lang')) { $paths += 'lang' }
    git diff --quiet $applicationSha $packagingSha -- @paths
    if ($LASTEXITCODE -eq 1) { throw 'Runtime source changed after the application release SHA.' }
    if ($LASTEXITCODE -gt 1) { throw 'Cannot compare application and packaging revisions.' }
    git archive --format=zip "--output=$sourceArchive" $packagingSha -- @paths
    if ($LASTEXITCODE -ne 0) { throw 'Source archive failed.' }
    New-Item -ItemType Directory -Path $output | Out-Null
    [IO.Compression.ZipFile]::ExtractToDirectory($sourceArchive, $output)
    Copy-Item -LiteralPath $build -Destination (Join-Path $output 'public/build') -Recurse
    foreach ($directory in @('bootstrap/cache', 'storage/app/private', 'storage/app/public', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs')) {
        New-Item -ItemType Directory -Path (Join-Path $output $directory) -Force | Out-Null
    }
    Push-Location $output
    try {
        composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
        if ($LASTEXITCODE -ne 0) { throw 'Production Composer installation failed.' }
        composer check-platform-reqs --no-dev
        if ($LASTEXITCODE -ne 0) { throw 'Production platform requirements failed.' }
        composer audit --locked --no-dev
        if ($LASTEXITCODE -ne 0) { throw 'Production vendor security audit failed.' }
    } finally { Pop-Location }
    Copy-Item -LiteralPath (Join-Path $source 'docs/deployment/upload-artifact-readme.txt') -Destination (Join-Path $output 'DEPLOYMENT-README.txt')
    # This is build metadata, not a final validation or deployment approval.
    $metadata = [ordered]@{
        version = $Version
        application_release_sha = $applicationSha
        packaging_sha = $packagingSha
        created = (Get-Date).ToString('o')
        source_composer_lock_sha256 = (Get-FileHash -LiteralPath (Join-Path $source 'composer.lock') -Algorithm SHA256).Hash
        source_package_lock_sha256 = (Get-FileHash -LiteralPath (Join-Path $source 'package-lock.json') -Algorithm SHA256).Hash
    }
    $metadata | ConvertTo-Json | Set-Content -LiteralPath (Join-Path $output 'BUILD-PROVENANCE.json') -Encoding utf8NoBOM
    Remove-Item -LiteralPath $sourceArchive
    Remove-Item -LiteralPath $temporary
    Write-Output "ASSEMBLED FOR ISOLATED VERIFICATION: $output"
    Write-Output "APPLICATION_RELEASE_SHA=$applicationSha"
    Write-Output "PACKAGING_SHA=$packagingSha"
} finally { Pop-Location }
