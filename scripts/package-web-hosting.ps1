param(
    [string]$OutputDirectory = "release"
)

$ErrorActionPreference = "Stop"

$projectRoot = Resolve-Path (Join-Path $PSScriptRoot "..")
$timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
$releaseRoot = Join-Path $projectRoot $OutputDirectory
$stage = Join-Path $releaseRoot "web-hosting-$timestamp"
$zip = Join-Path $releaseRoot "shoe-web-hosting-$timestamp.zip"

New-Item -ItemType Directory -Force -Path $stage | Out-Null

Copy-Item -Path (Join-Path $projectRoot "backend") -Destination $stage -Recurse
Copy-Item -Path (Join-Path $projectRoot "web-admin") -Destination $stage -Recurse
Copy-Item -Path (Join-Path $projectRoot "storage") -Destination $stage -Recurse
Copy-Item -Path (Join-Path $projectRoot "index.php") -Destination $stage
Copy-Item -Path (Join-Path $projectRoot "README.md") -Destination $stage
Copy-Item -Path (Join-Path $projectRoot "DEPLOYMENT.md") -Destination $stage

Compress-Archive -Path (Join-Path $stage "*") -DestinationPath $zip -Force

Write-Host "Created web hosting package:"
Write-Host $zip
