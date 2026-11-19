@echo off
setlocal
chcp 65001 >nul
title Vite Digital - All-in-One Setup & Launcher

set "APP_DIR=%~dp0"
if "%APP_DIR:~-1%"=="\" set "APP_DIR=%APP_DIR:~0,-1%"

:: 1. Check if Scheduled Task exists; elevate once if needed to register it
schtasks /query /tn "ViteDigitalDailyScheduleNotify" >nul 2>&1
if %errorlevel% neq 0 (
    net session >nul 2>&1
    if %errorlevel% neq 0 (
        echo Requesting Administrator permission to register Daily Notify Task...
        powershell -NoProfile -ExecutionPolicy Bypass -Command "Start-Process -FilePath '%~f0' -Verb RunAs"
        exit /b
    )
)

echo ===================================================
echo   Vite Digital - Setup & System Tray Launcher
echo ===================================================

:: 2. Register Task Scheduler (if Admin), Desktop Shortcut, and Auto-Start
powershell -NoProfile -ExecutionPolicy Bypass -Command ^
  "$dir = '%APP_DIR%';" ^
  "$vbsTray = Join-Path $dir 'run-tray.vbs';" ^
  "$vbsNotify = Join-Path $dir 'run-daily-notify.vbs';" ^
  "$ico = Join-Path $dir 'public\app.ico';" ^
  "$isAdmin = ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator);" ^
  "if ($isAdmin -and (Test-Path $vbsNotify)) {" ^
  "  $taskName = 'ViteDigitalDailyScheduleNotify';" ^
  "  $existing = Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue;" ^
  "  if (-not $existing) {" ^
  "    $action = New-ScheduledTaskAction -Execute 'wscript.exe' -Argument ('//B //Nologo \"' + $vbsNotify + '\"') -WorkingDirectory $dir;" ^
  "    $trigger = New-ScheduledTaskTrigger -Once -At (Get-Date).Date -RepetitionInterval (New-TimeSpan -Minutes 1);" ^
  "    $settings = New-ScheduledTaskSettingsSet -StartWhenAvailable -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -MultipleInstances IgnoreNew;" ^
  "    Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $trigger -Settings $settings -Description 'Silently polls every 1 minute and sends Vite Digital daily notifications' -Force | Out-Null;" ^
  "    Write-Host '[OK] Registered Scheduled Task: ViteDigitalDailyScheduleNotify' -ForegroundColor Green;" ^
  "  } else {" ^
  "    Write-Host '[OK] Scheduled Task already registered.' -ForegroundColor Cyan;" ^
  "  }" ^
  "}" ^
  "$wsh = New-Object -ComObject WScript.Shell;" ^
  "foreach ($folder in @([Environment]::GetFolderPath('Desktop'), [Environment]::GetFolderPath('Startup'))) {" ^
  "  $lnkPath = Join-Path $folder 'Vite Digital.lnk';" ^
  "  $sc = $wsh.CreateShortcut($lnkPath);" ^
  "  $sc.TargetPath = 'wscript.exe';" ^
  "  $sc.Arguments = '\"' + $vbsTray + '\"';" ^
  "  $sc.WorkingDirectory = $dir;" ^
  "  if (Test-Path $ico) { $sc.IconLocation = $ico + ', 0'; }" ^
  "  $sc.Description = 'CNMH DigitalHealth (Vite Digital) System Tray';" ^
  "  $sc.Save();" ^
  "}" ^
  "Write-Host '[OK] Desktop Shortcut & Auto-Start ready.' -ForegroundColor Green;"

:: 3. Launch System Tray silently
echo Starting Vite Digital in System Tray...
start "" wscript.exe "%APP_DIR%\run-tray.vbs"
timeout /t 2 >nul
exit /b 0

