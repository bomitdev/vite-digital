@echo off
chcp 65001 >nul
echo ===================================================
echo   Enabling Windows Auto-Start for Vite Digital Tray
echo ===================================================
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0enable-autostart.ps1"
pause
