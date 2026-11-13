@echo off
cd /d "%~dp0"
set PHP_EXE=C:\xampp\php\php.exe
if not exist "%PHP_EXE%" set PHP_EXE=php

"%PHP_EXE%" "%~dp0cron_daily_schedule_notify.php" --auto
"%PHP_EXE%" "%~dp0cron_daily_repair_notify.php" --auto
