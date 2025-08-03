@echo off
echo Starting Laravel Queue Worker...
php artisan queue:work --tries=3 --timeout=60
pause
