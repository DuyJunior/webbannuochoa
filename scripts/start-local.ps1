param([string]$Php = "php", [int]$Port = 8002)
$ErrorActionPreference = "Stop"
Set-Location (Split-Path $PSScriptRoot -Parent)
$phpCommand = (Get-Command $Php -ErrorAction Stop).Source
$listener = Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue
if ($listener) { throw "Port $Port already in use. Choose another -Port; no existing process was stopped." }
$worker = Start-Process -FilePath $phpCommand -ArgumentList @("artisan", "queue:work", "database", "--queue=ai-chat", "--sleep=1", "--timeout=40", "--tries=10") -WindowStyle Hidden -PassThru -RedirectStandardOutput "storage/logs/demo-worker.out.log" -RedirectStandardError "storage/logs/demo-worker.err.log"
try {
    $scheduler = Start-Process -FilePath $phpCommand -ArgumentList @("artisan", "schedule:work") -WindowStyle Hidden -PassThru -RedirectStandardOutput "storage/logs/demo-scheduler.out.log" -RedirectStandardError "storage/logs/demo-scheduler.err.log"
    Write-Host "Website: http://127.0.0.1:$Port — Ctrl+C stops this run, its AI worker and scheduler."
    & $phpCommand artisan serve --host=127.0.0.1 --port=$Port
} finally {
    if (-not $worker.HasExited) { Stop-Process -Id $worker.Id -ErrorAction SilentlyContinue }
    if ($scheduler -and -not $scheduler.HasExited) { Stop-Process -Id $scheduler.Id -ErrorAction SilentlyContinue }
}
