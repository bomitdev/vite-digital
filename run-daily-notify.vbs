' Vite Digital - Completely Silent Daily Schedule Notify Runner
Set WshShell = CreateObject("WScript.Shell")
Set fso = CreateObject("Scripting.FileSystemObject")
currentDir = fso.GetParentFolderName(WScript.ScriptFullName)
WshShell.CurrentDirectory = currentDir

WshShell.Run """" & currentDir & "\run-daily-notify.bat""", 0, False

