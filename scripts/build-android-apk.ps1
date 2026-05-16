param(
    [Parameter(Mandatory = $true)]
    [string]$ApiUrl
)

$ErrorActionPreference = "Stop"

$projectRoot = Resolve-Path (Join-Path $PSScriptRoot "..")
$mobileRoot = Join-Path $projectRoot "mobile-app"

Set-Location $mobileRoot
$env:EXPO_PUBLIC_API_BASE_URL = $ApiUrl.TrimEnd("/")

Write-Host "Using API:"
Write-Host $env:EXPO_PUBLIC_API_BASE_URL
Write-Host ""
Write-Host "Log in to Expo when asked. The finished build will print an APK download link."

npx.cmd eas-cli@latest login
npx.cmd eas-cli@latest build --platform android --profile preview
