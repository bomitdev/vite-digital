# ==============================================================================
# Vite Digital (CNMH DigitalHealth) - System Tray Runner
# Manages web app server in the background with a Windows System Tray icon
# ==============================================================================

[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$OutputEncoding = [System.Text.Encoding]::UTF8

# 1. Detect Host & Port from .env
$port = 5173
$hostName = "localhost"
$envFile = Join-Path $PSScriptRoot ".env"

if (Test-Path $envFile) {
    Get-Content $envFile | ForEach-Object {
        if ($_ -match "^\s*VITE_SERVER_PORT\s*=\s*(\d+)") {
            $port = [int]$matches[1]
        } elseif ($_ -match "^\s*PORT\s*=\s*(\d+)") {
            $port = [int]$matches[1]
        }
        if ($_ -match "^\s*VITE_SERVER_HOST\s*=\s*(.+)") {
            $h = $matches[1].Trim()
            if ($h -and $h -ne "0.0.0.0") {
                $hostName = $h
            }
        }
    }
}
$appUrl = "http://${hostName}:$port"

# 2. Single Instance Check (Prevents duplicate tray instances)
$mutexName = "Global\ViteDigitalTrayRunnerMutex"
$createdNew = $false
$mutex = New-Object System.Threading.Mutex($true, $mutexName, [ref]$createdNew)

if (-not $createdNew) {
    # Another instance is already running; open browser and exit silently
    Start-Process $appUrl
    exit
}

# 3. Load Windows Forms and Drawing assemblies
Add-Type -AssemblyName System.Windows.Forms
Add-Type -AssemblyName System.Drawing

# 4. State & Process Handles
$global:appProcess = $null
$logFile = Join-Path $PSScriptRoot "tray.log"

function Write-TrayLog($msg) {
    $time = (Get-Date).ToString("yyyy-MM-dd HH:mm:ss")
    Add-Content -Path $logFile -Value "[$time] $msg" -Encoding UTF8 -ErrorAction SilentlyContinue
}

function Test-PortActive($checkHost, $checkPort) {
    try {
        $tcp = New-Object System.Net.Sockets.TcpClient
        $iar = $tcp.BeginConnect($checkHost, $checkPort, $null, $null)
        $wait = $iar.AsyncWaitHandle.WaitOne(600)
        if ($wait -and $tcp.Connected) {
            $tcp.EndConnect($iar)
            $tcp.Close()
            return $true
        }
        $tcp.Close()
    } catch {}
    return $false
}

function Start-AppServer {
    try {
        # Check if node is installed
        $nodeCmd = Get-Command node -ErrorAction SilentlyContinue
        if (-not $nodeCmd) {
            [System.Windows.Forms.MessageBox]::Show(
                "Node.js not found. Please install Node.js first.",
                "Vite Digital Error",
                [System.Windows.Forms.MessageBoxButtons]::OK,
                [System.Windows.Forms.MessageBoxIcon]::Error
            )
            return $false
        }

        # Check if port is already listening
        if (Test-PortActive $hostName $port) {
            Write-TrayLog "Port $port is already active. Attached to existing server."
            return $true
        }

        # Stop any existing orphan server processes in this directory
        Stop-AppServer

        $pinfo = New-Object System.Diagnostics.ProcessStartInfo
        $pinfo.FileName = "node"
        
        $serverJs = Join-Path $PSScriptRoot "server.js"
        $viteJs = Join-Path $PSScriptRoot "node_modules\vite\bin\vite.js"

        if (Test-Path $serverJs) {
            $pinfo.Arguments = "server.js"
        } elseif (Test-Path $viteJs) {
            $pinfo.Arguments = "`"$viteJs`""
        } else {
            $pinfo.FileName = "cmd.exe"
            $pinfo.Arguments = "/c npm run dev"
        }

        $pinfo.WorkingDirectory = $PSScriptRoot
        $pinfo.WindowStyle = [System.Diagnostics.ProcessWindowStyle]::Hidden
        $pinfo.CreateNoWindow = $true
        $pinfo.UseShellExecute = $false
        $pinfo.RedirectStandardOutput = $true
        $pinfo.RedirectStandardError = $true

        $global:appProcess = [System.Diagnostics.Process]::Start($pinfo)
        Write-TrayLog "Started app server (PID: $($global:appProcess.Id)) on port $port"
        return $true
    } catch {
        Write-TrayLog "Failed to start server: $_"
        return $false
    }
}

function Stop-AppServer {
    if ($global:appProcess -and -not $global:appProcess.HasExited) {
        try {
            Write-TrayLog "Stopping app server (PID: $($global:appProcess.Id))..."
            $global:appProcess.Kill()
            $global:appProcess.WaitForExit(3000)
        } catch {}
    }

    # Safety cleanup for leftover vite processes in this directory
    try {
        Get-CimInstance Win32_Process | Where-Object {
            ($_.CommandLine -like "*vite.js*" -or $_.CommandLine -like "*vite-digital*") -and $_.ProcessId -ne $PID
        } | ForEach-Object {
            Stop-Process -Id $_.ProcessId -Force -ErrorAction SilentlyContinue
        }
    } catch {}

    $global:appProcess = $null
}

function Restart-AppServer {
    Stop-AppServer
    Start-Sleep -Milliseconds 800
    $started = Start-AppServer
    if ($started) {
        $tray.ShowBalloonTip(3000, "Vite Digital", "Server restarted successfully", [System.Windows.Forms.ToolTipIcon]::Info)
    } else {
        $tray.ShowBalloonTip(3000, "Vite Digital", "Failed to restart server", [System.Windows.Forms.ToolTipIcon]::Error)
    }
}

# 5. Load Icon
$iconPath = Join-Path $PSScriptRoot "public\app.ico"
$trayIcon = $null
if (Test-Path $iconPath) {
    try {
        $trayIcon = New-Object System.Drawing.Icon($iconPath)
    } catch {
        $trayIcon = [System.Drawing.SystemIcons]::Application
    }
} else {
    $trayIcon = [System.Drawing.SystemIcons]::Application
}

# 6. Initialize NotifyIcon & Context Menu
$tray = New-Object System.Windows.Forms.NotifyIcon
$tray.Icon = $trayIcon
$tray.Text = "CNMH DigitalHealth (Vite Digital)"
$tray.Visible = $true

$contextMenu = New-Object System.Windows.Forms.ContextMenuStrip
$fontBold = New-Object System.Drawing.Font("Segoe UI", 9, [System.Drawing.FontStyle]::Bold)
$fontNormal = New-Object System.Drawing.Font("Segoe UI", 9, [System.Drawing.FontStyle]::Regular)

# Menu: Open Web App
$menuOpen = $contextMenu.Items.Add("Open Web App (เปิดระบบ)")
$menuOpen.Font = $fontBold
$menuOpen.add_Click({
    Start-Process $appUrl
})

# Menu: Copy URL
$menuCopy = $contextMenu.Items.Add("Copy URL ($appUrl)")
$menuCopy.Font = $fontNormal
$menuCopy.add_Click({
    [System.Windows.Forms.Clipboard]::SetText($appUrl)
    $tray.ShowBalloonTip(2000, "Vite Digital", "URL copied to clipboard: $appUrl", [System.Windows.Forms.ToolTipIcon]::Info)
})

# Menu: Restart Server
$menuRestart = $contextMenu.Items.Add("Restart Server (รีสตาร์ท)")
$menuRestart.Font = $fontNormal
$menuRestart.add_Click({
    Restart-AppServer
})

# Menu: Open Folder
$menuFolder = $contextMenu.Items.Add("Open Folder (เปิดโฟลเดอร์)")
$menuFolder.Font = $fontNormal
$menuFolder.add_Click({
    Start-Process "explorer.exe" $PSScriptRoot
})

# Separator
[void]$contextMenu.Items.Add("-")

# Menu: Exit
$menuExit = $contextMenu.Items.Add("Exit (ปิดโปรแกรม)")
$menuExit.Font = $fontNormal
$menuExit.add_Click({
    Stop-AppServer
    $tray.Visible = $false
    $tray.Dispose()
    if ($mutex) {
        $mutex.ReleaseMutex()
        $mutex.Dispose()
    }
    [System.Windows.Forms.Application]::Exit()
})

$tray.ContextMenuStrip = $contextMenu

# Double Click action: Open Browser
$tray.add_DoubleClick({
    Start-Process $appUrl
})

# 7. Start App Server
$started = Start-AppServer

if ($started) {
    $tray.ShowBalloonTip(
        3500,
        "Vite Digital Ready",
        "Running on $appUrl`nDouble-click icon to open in browser",
        [System.Windows.Forms.ToolTipIcon]::Info
    )
}

# 8. Clean Exit on Process / Windows Shutdown
$exitAction = {
    Stop-AppServer
    if ($tray) {
        $tray.Visible = $false
        $tray.Dispose()
    }
}
Register-EngineEvent -SourceIdentifier ([System.Management.Automation.PsEngineEvent]::Exiting) -Action $exitAction | Out-Null

# 9. Keep PowerShell Forms Event Loop Running
[System.Windows.Forms.Application]::Run()
