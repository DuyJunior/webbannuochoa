param([string]$Php = "php", [string]$Composer = "composer")
$ErrorActionPreference = "Stop"
Set-Location (Split-Path $PSScriptRoot -Parent)
function Run-Step([string]$Program, [string[]]$Arguments) {
    & $Program @Arguments
    if ($LASTEXITCODE -ne 0) { throw "Step failed: $Program $($Arguments -join ' ')" }
}
if (-not (Test-Path -LiteralPath ".env")) {
    Copy-Item -LiteralPath ".env.example" -Destination ".env"
}
if (-not (Test-Path -LiteralPath "database/database.sqlite")) {
    New-Item -ItemType File -Path "database/database.sqlite" | Out-Null
}
Run-Step $Composer @("install", "--no-interaction")
Run-Step $Php @("artisan", "config:clear")
$localConfig = Get-Content -LiteralPath ".env"
if (-not ($localConfig | Where-Object { $_ -match '^APP_KEY=.+$' })) {
    Run-Step $Php @("artisan", "key:generate")
}
Run-Step $Php @("scripts/backup-sqlite.php")
Run-Step $Php @("artisan", "migrate", "--force")
Run-Step $Php @("artisan", "db:seed", "--class=LocalDemoSeeder", "--force")
Run-Step "npm.cmd" @("ci", "--no-audit", "--no-fund")
Run-Step "npm.cmd" @("run", "build")
Write-Host "Ready. Run scripts/start-local.ps1. Existing .env and populated catalog were preserved."
Write-Host "AI requires your local GROQ_API_KEY and the Free-plan flags described in README.md."
