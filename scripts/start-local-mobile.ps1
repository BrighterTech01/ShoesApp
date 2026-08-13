param(
    [string]$BackendHost = "",
    [int]$BackendPort = 8000,
    [ValidateSet("dev", "go", "android:go", "web")]
    [string]$Mode = "dev"
)

$ErrorActionPreference = "Stop"

$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot "..")).Path
$mobileAppPath = Join-Path $projectRoot "mobile-app"

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

if ([string]::IsNullOrWhiteSpace($BackendHost)) {
    $BackendHost = Get-PreferredLocalIp
}

$env:EXPO_PUBLIC_API_BASE_URL = "http://$BackendHost`:$BackendPort/backend"

Write-Host ""
Write-Host "Local mobile app is starting..." -ForegroundColor Cyan
Write-Host "Expo API Base URL: $($env:EXPO_PUBLIC_API_BASE_URL)" -ForegroundColor Yellow
Write-Host ""

Set-Location $mobileAppPath
& npm.cmd run $Mode
