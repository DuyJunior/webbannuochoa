param([string]$Php = "php", [int]$Port = 8002)
$ErrorActionPreference = "Stop"
Set-Location (Split-Path $PSScriptRoot -Parent)
$phpCommand = (Get-Command $Php -ErrorAction Stop).Source
$listener = Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue
if ($listener) { throw "Port $Port already in use. Choose another -Port; no existing process was stopped." }
$manifest = Join-Path (Get-Location) "public/build/manifest.json"
if (-not (Test-Path -LiteralPath $manifest)) { throw "Frontend assets are missing. Run npm ci and npm run build first." }
# This launcher serves the built assets, not a Vite dev server.
$viteHotFile = Join-Path (Get-Location) "public/hot"
if (Test-Path -LiteralPath $viteHotFile) { Remove-Item -LiteralPath $viteHotFile -Force }
& $phpCommand artisan orders:dispatch-emails --check --no-interaction
if ($LASTEXITCODE -ne 0) { throw "Order email schema is not ready. Run php artisan migrate --force before starting." }
$worker = Start-Process -FilePath $phpCommand -ArgumentList @("artisan", "queue:work", "database", "--queue=default,ai-chat", "--sleep=1", "--timeout=40", "--tries=10") -WindowStyle Hidden -PassThru -RedirectStandardOutput "storage/logs/demo-worker.out.log" -RedirectStandardError "storage/logs/demo-worker.err.log"
try {
    $scheduler = Start-Process -FilePath $phpCommand -ArgumentList @("artisan", "schedule:work") -WindowStyle Hidden -PassThru -RedirectStandardOutput "storage/logs/demo-scheduler.out.log" -RedirectStandardError "storage/logs/demo-scheduler.err.log"
    Write-Host "Website: http://127.0.0.1:$Port - Ctrl+C stops this run, its email/AI worker and scheduler."
    & $phpCommand artisan serve --host=127.0.0.1 --port=$Port
} finally {
    if (-not $worker.HasExited) { Stop-Process -Id $worker.Id -ErrorAction SilentlyContinue }
    if ($scheduler -and -not $scheduler.HasExited) { Stop-Process -Id $scheduler.Id -ErrorAction SilentlyContinue }
}
