$WshShell = New-Object -ComObject WScript.Shell
$startup = [System.Environment]::GetFolderPath("Startup")
$shortcutPath = Join-Path $startup "Vite Digital.lnk"
$shortcut = $WshShell.CreateShortcut($shortcutPath)
$shortcut.TargetPath = "wscript.exe"
$shortcut.Arguments = "`"$PSScriptRoot\run-tray.vbs`""
$shortcut.WorkingDirectory = $PSScriptRoot

$icoPath = Join-Path $PSScriptRoot "public\app.ico"
if (Test-Path $icoPath) {
    $shortcut.IconLocation = "$icoPath, 0"
}

$shortcut.Description = "CNMH DigitalHealth (Vite Digital) Tray Auto-Start"
$shortcut.Save()

Write-Host "Enabled auto-start successfully! App will run minimized in System Tray on Windows boot." -ForegroundColor Green
