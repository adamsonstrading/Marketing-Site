@echo off
echo Starting Continuous Queue Worker...
echo This will process emails continuously until stopped (Ctrl+C)
echo.
php artisan queue:work --queue=emails --tries=3 --timeout=120 --max-jobs=1000
pause

