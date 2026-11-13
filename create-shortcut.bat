@echo off
chcp 65001 >nul
echo ===================================================
echo   Creating Desktop Shortcut for Vite Digital Tray
echo ===================================================
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0create-desktop-shortcut.ps1"
pause
