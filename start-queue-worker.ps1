# Email Campaign Queue Worker Startup Script
Write-Host "=== Email Campaign Queue Worker ===" -ForegroundColor Green
Write-Host "Starting queue worker..." -ForegroundColor Yellow
Write-Host ""
Write-Host "Queue Worker will process email jobs continuously." -ForegroundColor Cyan
Write-Host "Press Ctrl+C to stop." -ForegroundColor Yellow
Write-Host ""

# Change to script directory
Set-Location $PSScriptRoot

# Start queue worker
php artisan queue:work --queue=emails --tries=3 --timeout=60 --max-jobs=1000 --max-time=3600

Write-Host ""
Write-Host "Queue worker stopped." -ForegroundColor Red
Read-Host "Press Enter to exit"

