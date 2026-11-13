@echo off
chcp 65001 >nul
echo ===================================================
echo   Stopping Vite Digital (System Tray ^& Server)
echo ===================================================
powershell -NoProfile -ExecutionPolicy Bypass -Command ^
  "Get-CimInstance Win32_Process | Where-Object { $_.CommandLine -like '*tray-runner.ps1*' -or $_.CommandLine -like '*vite.js*' } | ForEach-Object { Stop-Process -Id $_.ProcessId -Force -ErrorAction SilentlyContinue }; Write-Host 'Vite Digital System Tray has been stopped.' -ForegroundColor Green"
ping -n 2 127.0.0.1 >nul
