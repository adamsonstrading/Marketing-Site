# Windows Task Scheduler Setup Script for Email Campaign Queue Worker
# Run this script as Administrator

Write-Host "=== Windows Task Scheduler Setup ===" -ForegroundColor Green
Write-Host "This will create a scheduled task to automatically process email queues" -ForegroundColor Yellow
Write-Host ""

$scriptPath = $PSScriptRoot
$phpPath = (Get-Command php).Source
$artisanPath = Join-Path $scriptPath "artisan"

Write-Host "Script Path: $scriptPath" -ForegroundColor Cyan
Write-Host "PHP Path: $phpPath" -ForegroundColor Cyan
Write-Host "Artisan Path: $artisanPath" -ForegroundColor Cyan
Write-Host ""

# Check if running as Administrator
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)

if (-not $isAdmin) {
    Write-Host "ERROR: This script must be run as Administrator!" -ForegroundColor Red
    Write-Host "Right-click PowerShell and select 'Run as Administrator'" -ForegroundColor Yellow
    pause
    exit 1
}

# Task name
$taskName = "EmailCampaignQueueProcessor"

# Check if task already exists
$existingTask = Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue

if ($existingTask) {
    Write-Host "Task '$taskName' already exists. Removing..." -ForegroundColor Yellow
    Unregister-ScheduledTask -TaskName $taskName -Confirm:$false
}

# Create the action (run Laravel scheduler which processes queue every 30 seconds)
$action = New-ScheduledTaskAction -Execute $phpPath -Argument "`"$artisanPath`" schedule:run" -WorkingDirectory $scriptPath

# Create the trigger (every minute - Laravel scheduler handles the 30-second intervals internally)
$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Minutes 1) -RepetitionDuration (New-TimeSpan -Days 365)

# Create settings
$settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable -RunOnlyIfNetworkAvailable

# Create the principal (run as SYSTEM)
$principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest

# Register the task
Write-Host "Creating scheduled task..." -ForegroundColor Yellow
Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $trigger -Settings $settings -Principal $principal -Description "Automatically processes email campaign queue every minute"

Write-Host ""
Write-Host "✓ Scheduled task created successfully!" -ForegroundColor Green
Write-Host ""
Write-Host "Task Name: $taskName" -ForegroundColor Cyan
Write-Host "Runs: Every minute" -ForegroundColor Cyan
Write-Host "Command: php artisan queue:auto-process --max-jobs=50" -ForegroundColor Cyan
Write-Host ""
Write-Host "To view the task:" -ForegroundColor Yellow
Write-Host "  Task Scheduler > Task Scheduler Library > $taskName" -ForegroundColor White
Write-Host ""
Write-Host "To remove the task:" -ForegroundColor Yellow
Write-Host "  Unregister-ScheduledTask -TaskName '$taskName' -Confirm:`$false" -ForegroundColor White
Write-Host ""

pause

