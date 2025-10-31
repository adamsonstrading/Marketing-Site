@echo off
echo Starting Queue Worker for Email Campaigns...
echo.
echo Queue Worker Started at %date% %time%
echo Press Ctrl+C to stop the queue worker
echo.

cd /d "%~dp0"
php artisan queue:work --queue=emails --tries=3 --timeout=60

pause

