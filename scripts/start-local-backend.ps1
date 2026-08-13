param(
    [string]$ListenHost = "0.0.0.0",
    [int]$Port = 8000,
    [string]$DbHost = "127.0.0.1",
    [string]$DbPort = "3306",
    [string]$DbName = "shoesap",
    [string]$DbUser = "root",
    [string]$DbPass = "Barrientos90",
    [switch]$UseLocalhostUrls
)

$ErrorActionPreference = "Stop"

$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot "..")).Path
$phpCommand = Get-Command php -ErrorAction Stop
$phpPath = $phpCommand.Source

function Get-PreferredLocalIp {
    try {
        $ip = Get-NetIPAddress -AddressFamily IPv4 -ErrorAction Stop |
            Where-Object {
                $_.IPAddress -notlike "127.*" -and
                $_.IPAddress -notlike "169.254.*" -and
                $_.IPAddress -notlike "0.*"
            } |
            Sort-Object InterfaceMetric |
            Select-Object -First 1 -ExpandProperty IPAddress

        if ($ip) {
            return $ip
        }
    } catch {
    }

    return "127.0.0.1"
}

$publicHost = if ($UseLocalhostUrls) {
    "127.0.0.1"
} elseif ($ListenHost -eq "0.0.0.0") {
    Get-PreferredLocalIp
} else {
    $ListenHost
}

$env:APP_BASE_URL = "http://$publicHost`:$Port"
$env:BACKEND_URL = "$($env:APP_BASE_URL)/backend"
$env:UPLOADS_URL = "$($env:BACKEND_URL)/uploads"
$env:DB_HOST = $DbHost
$env:DB_PORT = $DbPort
$env:DB_NAME = $DbName
$env:DB_USER = $DbUser
$env:DB_PASS = $DbPass
$env:DB_AUTO_CREATE_DATABASE = "true"

Write-Host ""
Write-Host "Local backend is starting..." -ForegroundColor Cyan
Write-Host "Project Root: $projectRoot"
Write-Host "Web Admin: $($env:APP_BASE_URL)/web-admin/login.php" -ForegroundColor Green
Write-Host "Backend Health: $($env:BACKEND_URL)/health.php" -ForegroundColor Green
Write-Host "Mobile API Base URL: $($env:BACKEND_URL)" -ForegroundColor Yellow
Write-Host ""

Set-Location $projectRoot
& $phpPath -S "$ListenHost`:$Port" -t $projectRoot
