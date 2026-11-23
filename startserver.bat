@echo off
cd C:\sekolah\web_sekolah
start /b "" php artisan serve --host=0.0.0.0 --port=80
start /b "" php artisan schedule:work
start /b "" php artisan queue:work
