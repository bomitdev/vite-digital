@echo off
chcp 65001 >nul
echo ===================================================
echo   Disabling Windows Auto-Start for Vite Digital Tray
echo ===================================================
powershell -NoProfile -ExecutionPolicy Bypass -Command ^
  "$startup = [System.Environment]::GetFolderPath('Startup'); $path = Join-Path $startup 'Vite Digital.lnk'; if (Test-Path $path) { Remove-Item $path -Force; Write-Host 'Disabled auto-start successfully.' -ForegroundColor Yellow } else { Write-Host 'No auto-start shortcut found.' -ForegroundColor Cyan }"
pause
