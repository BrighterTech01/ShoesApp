param(
    [int]$BackendPort = 8000,
    [int]$ExpoPort = 8085,
    [string]$DbHost = "127.0.0.1",
    [string]$DbPort = "3306",
    [string]$DbName = "shoesap",
    [string]$DbUser = "root",
    [string]$DbPass = "Barrientos90"
)

$ErrorActionPreference = "Stop"

$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot "..")).Path
$mobileAppPath = Join-Path $projectRoot "mobile-app"
$adbPath = Join-Path $env:LOCALAPPDATA "Android\Sdk\platform-tools\adb.exe"
$phpPath = if (Test-Path "C:\xampp\php\php.exe") {
    "C:\xampp\php\php.exe"
} else {
    (Get-Command php -ErrorAction Stop).Source
}

if (-not (Test-Path $adbPath)) {
    throw "ADB was not found at $adbPath"
}

function Test-HttpOk {
    param([string]$Url)

    try {
        $response = Invoke-WebRequest -Uri $Url -UseBasicParsing -TimeoutSec 5
        return $response.StatusCode -ge 200 -and $response.StatusCode -lt 300
    } catch {
        return $false
    }
}

function Get-ListeningPid {
    param([int]$Port)

    $line = cmd /c "netstat -ano | findstr :$Port" |
        Select-String -Pattern "LISTENING" |
        Select-Object -First 1

    if ($line) {
        return (($line.ToString() -split "\s+") | Where-Object { $_ -ne "" } | Select-Object -Last 1)
    }

    return $null
}

function Test-PortFree {
    param([int]$Port)

    return (Get-ListeningPid $Port) -eq $null
}

function Get-FreePort {
    param([int]$PreferredPort)

    if (Test-PortFree $PreferredPort) {
        return $PreferredPort
    }

    for ($port = $PreferredPort + 1; $port -le ($PreferredPort + 50); $port++) {
        if (Test-PortFree $port) {
            return $port
        }
    }

    throw "No free port found from $PreferredPort to $($PreferredPort + 50)."
}

$requestedBackendPort = $BackendPort
$requestedExpoPort = $ExpoPort
$BackendPort = Get-FreePort $BackendPort
$ExpoPort = Get-FreePort $ExpoPort

if ($BackendPort -ne $requestedBackendPort) {
    Write-Host "Backend port $requestedBackendPort is busy, using $BackendPort instead." -ForegroundColor Yellow
}

if ($ExpoPort -ne $requestedExpoPort) {
    Write-Host "Expo port $requestedExpoPort is busy, using $ExpoPort instead." -ForegroundColor Yellow
}

$backendUrl = "http://127.0.0.1:$BackendPort/backend"
$backendHealthUrl = "$backendUrl/health.php"

if (-not (Test-HttpOk $backendHealthUrl)) {
    $backendOut = Join-Path $projectRoot "usb-backend.out.log"
    $backendErr = Join-Path $projectRoot "usb-backend.err.log"

    $env:APP_BASE_URL = "http://127.0.0.1:$BackendPort"
    $env:BACKEND_URL = $backendUrl
    $env:UPLOADS_URL = "$backendUrl/uploads"
    $env:DB_HOST = $DbHost
    $env:DB_PORT = $DbPort
    $env:DB_NAME = $DbName
    $env:DB_USER = $DbUser
    $env:DB_PASS = $DbPass
    $env:DB_AUTO_CREATE_DATABASE = "true"

    Start-Process `
        -FilePath $phpPath `
        -ArgumentList "-S", "127.0.0.1:$BackendPort", "-t", $projectRoot `
        -WorkingDirectory $projectRoot `
        -RedirectStandardOutput $backendOut `
        -RedirectStandardError $backendErr `
        -WindowStyle Hidden | Out-Null

    Start-Sleep -Seconds 4
}

if (-not (Test-HttpOk $backendHealthUrl)) {
    throw "The local PHP backend did not start at $backendHealthUrl"
}

& $adbPath reverse "tcp:$BackendPort" "tcp:$BackendPort" | Out-Null
& $adbPath reverse "tcp:$ExpoPort" "tcp:$ExpoPort" | Out-Null

& (Join-Path $PSScriptRoot "patch-expo-go-local-dev.ps1")

$env:EXPO_PUBLIC_API_BASE_URL = $backendUrl

Write-Host ""
Write-Host "USB Expo setup is ready." -ForegroundColor Cyan
Write-Host "Backend: $backendUrl" -ForegroundColor Green
Write-Host "Expo: exp://127.0.0.1:$ExpoPort" -ForegroundColor Green
Write-Host "ADB reverse:" -ForegroundColor Yellow
& $adbPath reverse --list
Write-Host ""
Write-Host "When Metro opens, press a to launch Expo Go on the phone." -ForegroundColor Yellow
Write-Host ""

Set-Location $mobileAppPath
& npx.cmd expo start --go --host localhost --port $ExpoPort -c
