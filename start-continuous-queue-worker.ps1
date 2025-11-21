# Continuous Queue Worker for Email Campaigns
# This script runs the queue worker continuously to process all emails

Write-Host "Starting Continuous Queue Worker..." -ForegroundColor Green
Write-Host "This will process emails continuously until stopped (Ctrl+C)" -ForegroundColor Yellow
Write-Host ""

# Change to project directory
Set-Location $PSScriptRoot

# Run queue worker continuously
php artisan queue:work --queue=emails --tries=3 --timeout=120 --max-jobs=1000

Write-Host ""
Write-Host "Queue worker stopped." -ForegroundColor Red
Read-Host "Press Enter to exit"

