[CmdletBinding()]
param(
    [switch]$SkipComposerInstall
)

$ErrorActionPreference = "Stop"
$ProjectRoot = (Resolve-Path (Join-Path $PSScriptRoot "..")).Path
Set-Location $ProjectRoot

function Invoke-Checked {
    param(
        [Parameter(Mandatory = $true)][string]$Command,
        [Parameter(Mandatory = $true)][string[]]$Arguments
    )

    & $Command @Arguments
    if ($LASTEXITCODE -ne 0) {
        throw "Command failed (exit $LASTEXITCODE): $Command $($Arguments -join ' ')"
    }
}

function Get-DotEnvValue {
    param([string]$Name, [string]$Content)

    $match = [regex]::Match(
        $Content,
        "(?m)^\s*$([regex]::Escape($Name))\s*=\s*(.*)\s*$"
    )

    if (-not $match.Success) { return $null }

    $value = $match.Groups[1].Value.Trim()
    if (($value.StartsWith('"') -and $value.EndsWith('"')) -or
        ($value.StartsWith("'") -and $value.EndsWith("'"))) {
        $value = $value.Substring(1, $value.Length - 2)
    }

    return $value
}

foreach ($tool in @("php", "composer")) {
    if (-not (Get-Command $tool -ErrorAction SilentlyContinue)) {
        throw "$tool tidak ditemukan di PATH. Install PHP/Composer dahulu."
    }
}

$envPath = Join-Path $ProjectRoot ".env"
$examplePath = Join-Path $ProjectRoot ".env.example"

if (-not (Test-Path $envPath)) {
    if (-not (Test-Path $examplePath)) {
        throw ".env dan .env.example tidak ditemukan."
    }

    Copy-Item $examplePath $envPath
    Write-Host ".env dibuat dari .env.example." -ForegroundColor Yellow
}

$envContent = Get-Content $envPath -Raw
$appEnv = Get-DotEnvValue -Name "APP_ENV" -Content $envContent
$dbConnection = Get-DotEnvValue -Name "DB_CONNECTION" -Content $envContent
$dbUrl = Get-DotEnvValue -Name "DB_URL" -Content $envContent
$dbDatabase = Get-DotEnvValue -Name "DB_DATABASE" -Content $envContent

if ($appEnv -ne "local") {
    throw "Dibatalkan: APP_ENV harus persis 'local'. Nilai saat ini: '$appEnv'. Jangan jalankan seeder demo pada staging bersama atau produksi."
}

if ($dbConnection -ne "sqlite") {
    throw "Dibatalkan: DB_CONNECTION harus 'sqlite'. Script ini tidak boleh menyentuh MySQL/PostgreSQL."
}

if (-not [string]::IsNullOrWhiteSpace($dbUrl)) {
    throw "Dibatalkan: DB_URL harus kosong agar koneksi tetap memakai file SQLite lokal."
}

if ([string]::IsNullOrWhiteSpace($dbDatabase)) {
    $dbDatabase = "database/database.sqlite"
}

if ($dbDatabase -eq ":memory:") {
    throw "DB_DATABASE=:memory: tidak cocok untuk server lokal persisten. Gunakan database/database.sqlite."
}

# Prevent inherited machine/user environment variables from silently
# redirecting Laravel to a different application environment or database.
if ($env:APP_ENV -and $env:APP_ENV -ne "local") {
    throw "Environment variable APP_ENV menimpa .env dan bukan 'local'."
}
if ($env:DB_CONNECTION -and $env:DB_CONNECTION -ne "sqlite") {
    throw "Environment variable DB_CONNECTION menimpa .env dan bukan 'sqlite'."
}
if ($env:DB_URL) {
    throw "Environment variable DB_URL harus kosong untuk local staging."
}

$databasePath = if ([System.IO.Path]::IsPathRooted($dbDatabase)) {
    $dbDatabase
} else {
    Join-Path $ProjectRoot $dbDatabase
}

$databaseDirectory = Split-Path -Parent $databasePath
if (-not (Test-Path $databaseDirectory)) {
    New-Item -ItemType Directory -Force -Path $databaseDirectory | Out-Null
}

if (-not (Test-Path $databasePath)) {
    New-Item -ItemType File -Path $databasePath | Out-Null
    Write-Host "SQLite lokal dibuat: $databasePath" -ForegroundColor Green
}

$phpModules = & php -m
if ($LASTEXITCODE -ne 0) {
    throw "Tidak dapat membaca daftar PHP extensions."
}

foreach ($extension in @("PDO", "pdo_sqlite", "fileinfo", "gd")) {
    if ($phpModules -notcontains $extension) {
        throw "PHP extension '$extension' belum aktif. Aktifkan extension tersebut pada php.ini."
    }
}

if (-not $SkipComposerInstall -and -not (Test-Path (Join-Path $ProjectRoot "vendor/autoload.php"))) {
    Write-Host "Installing Composer dependencies..." -ForegroundColor Cyan
    Invoke-Checked -Command "composer" -Arguments @("install")
}

if (-not (Test-Path (Join-Path $ProjectRoot "vendor/autoload.php"))) {
    throw "vendor/autoload.php belum tersedia. Jalankan composer install."
}

# Pin child PHP processes to this local SQLite database, regardless of
# unrelated machine-level environment variables.
$env:APP_ENV = "local"
$env:APP_DEBUG = "true"
$env:DB_CONNECTION = "sqlite"
$env:DB_DATABASE = $databasePath
$env:DB_URL = ""
$env:FILESYSTEM_DISK = "local"

Invoke-Checked -Command "php" -Arguments @("artisan", "optimize:clear")

$appKey = Get-DotEnvValue -Name "APP_KEY" -Content (Get-Content $envPath -Raw)
if ([string]::IsNullOrWhiteSpace($appKey)) {
    Invoke-Checked -Command "php" -Arguments @("artisan", "key:generate")
}
Invoke-Checked -Command "php" -Arguments @("artisan", "migrate", "--no-interaction")
Invoke-Checked -Command "php" -Arguments @(
    "artisan", "db:seed",
    "--class=Database\Seeders\StagingDemoSeeder",
    "--no-interaction"
)

$privateLogbookDirectory = Join-Path $ProjectRoot "storage/app/private/logbooks"
New-Item -ItemType Directory -Force -Path $privateLogbookDirectory | Out-Null

Write-Host ""
Write-Host "NEXORA local staging siap." -ForegroundColor Green
Write-Host "Health check : http://127.0.0.1:8000/up"
Write-Host "API base URL : http://127.0.0.1:8000/api"
Write-Host "Android AVD  : http://10.0.2.2:8000/api"
Write-Host ""
Write-Host "Akun dummy lokal (jangan dipakai pada server publik):" -ForegroundColor Yellow
Write-Host "Mahasiswa   : student@nexora.local"
Write-Host "Dosen       : lecturer@nexora.local"
Write-Host "Korlap      : coordinator@nexora.local"
Write-Host "Password    : NexoraLocal!2026"
Write-Host ""

Write-Host "IPv4 address untuk perangkat fisik:" -ForegroundColor Cyan
try {
    Get-NetIPAddress -AddressFamily IPv4 -ErrorAction Stop |
        Where-Object {
            $_.IPAddress -notlike "127.*" -and
            $_.IPAddress -notlike "169.254.*" -and
            $_.AddressState -eq "Preferred"
        } |
        Select-Object -ExpandProperty IPAddress -Unique |
        ForEach-Object { Write-Host ("  http://" + $_ + ":8000/api") }
} catch {
    Write-Host "Tidak dapat mendeteksi IP otomatis. Jalankan ipconfig untuk melihat IPv4."
}

Write-Host ""
Write-Host "Server hanya untuk jaringan pengembangan tepercaya. Tekan Ctrl+C untuk berhenti." -ForegroundColor Yellow
Invoke-Checked -Command "php" -Arguments @("artisan", "serve", "--host=0.0.0.0", "--port=8000")
