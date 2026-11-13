param(
    [string]$TaskName = "ViteDigitalDailyScheduleNotify",
    [int]$IntervalMinutes = 1
)

if ($IntervalMinutes -lt 1) {
    $IntervalMinutes = 1
}

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$vbsPath = Join-Path $scriptDir "run-daily-notify.vbs"

if (-not (Test-Path $vbsPath)) {
    Write-Error "Missing silent runner script: $vbsPath"
    exit 1
}

$action = New-ScheduledTaskAction -Execute "wscript.exe" -Argument "//B //Nologo `"$vbsPath`"" -WorkingDirectory $scriptDir
$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date).Date -RepetitionInterval (New-TimeSpan -Minutes $IntervalMinutes)
$settings = New-ScheduledTaskSettingsSet -StartWhenAvailable -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -MultipleInstances IgnoreNew

Register-ScheduledTask -TaskName $TaskName -Action $action -Trigger $trigger -Settings $settings -Description "Silently polls every $IntervalMinutes minute(s) and sends Vite Digital daily OPD card duty, leave, and official trip notifications at the web-configured notify_time" -Force

Write-Host "Registered Scheduled Task '$TaskName' running silently every $IntervalMinutes minute(s)." -ForegroundColor Green
