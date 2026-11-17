# PowerShell runner script for YukSehat Chatbot Server
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $ScriptDir

if (Get-Command node -ErrorAction SilentlyContinue) {
    Write-Host "Menggunakan Node.js sistem..." -ForegroundColor Green
    node server.js
} elseif (Test-Path "$ScriptDir\node.exe") {
    Write-Host "Menggunakan Node.js lokal ($ScriptDir\node.exe)..." -ForegroundColor Green
    & "$ScriptDir\node.exe" server.js
} else {
    Write-Error "node.exe tidak ditemukan di $ScriptDir!"
}
