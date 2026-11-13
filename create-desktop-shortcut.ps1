$WshShell = New-Object -ComObject WScript.Shell
$desktop = [System.Environment]::GetFolderPath("Desktop")
$shortcutPath = Join-Path $desktop "Vite Digital.lnk"
$shortcut = $WshShell.CreateShortcut($shortcutPath)
$shortcut.TargetPath = "wscript.exe"
$shortcut.Arguments = "`"$PSScriptRoot\run-tray.vbs`""
$shortcut.WorkingDirectory = $PSScriptRoot

$icoPath = Join-Path $PSScriptRoot "public\app.ico"
if (Test-Path $icoPath) {
    $shortcut.IconLocation = "$icoPath, 0"
}

$shortcut.Description = "CNMH DigitalHealth (Vite Digital) System Tray"
$shortcut.Save()

Write-Host "Created Desktop Shortcut: $shortcutPath" -ForegroundColor Green
