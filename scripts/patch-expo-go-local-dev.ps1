param()

$ErrorActionPreference = "Stop"

$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot "..")).Path
function Resolve-ExpoNodeModulePath {
    param(
        [string[]]$Candidates
    )

    foreach ($candidate in $Candidates) {
        $resolvedPath = Join-Path $projectRoot $candidate
        if (Test-Path $resolvedPath) {
            return $resolvedPath
        }
    }

    throw ("Required Expo file was not found in any supported location.`n" + ($Candidates -join "`n"))
}

$manifestMiddlewarePath = Resolve-ExpoNodeModulePath @(
    "mobile-app\node_modules\expo\node_modules\@expo\cli\build\src\start\server\middleware\ManifestMiddleware.js",
    "mobile-app\node_modules\@expo\cli\build\src\start\server\middleware\ManifestMiddleware.js"
)

$rewriteRequestUrlPath = Resolve-ExpoNodeModulePath @(
    "mobile-app\node_modules\expo\node_modules\@expo\metro-config\build\rewriteRequestUrl.js",
    "mobile-app\node_modules\@expo\metro-config\build\rewriteRequestUrl.js"
)

function Update-FileContent {
    param(
        [string]$Path,
        [string]$Find,
        [string]$Replace
    )

    if (-not (Test-Path $Path)) {
        throw "Required file not found: $Path"
    }

    $content = Get-Content $Path -Raw
    if ($content.Contains($Find)) {
        $updatedContent = $content.Replace($Find, $Replace)
        [System.IO.File]::WriteAllText($Path, $updatedContent, [System.Text.Encoding]::UTF8)
        return $true
    }

    if (-not $content.Contains($Replace)) {
        throw "Expected text was not found in $Path"
    }

    return $false
}

$manifestUpdated = Update-FileContent `
    -Path $manifestMiddlewarePath `
    -Find "            bytecode: engine === 'hermes'," `
    -Replace "            bytecode: false,"

$rewriteUpdated = Update-FileContent `
    -Path $rewriteRequestUrlPath `
    -Find "                    ensured.searchParams.set('transform.bytecode', '1');" `
    -Replace "                    ensured.searchParams.delete('transform.bytecode');"

if ($manifestUpdated -or $rewriteUpdated) {
    Write-Host "Expo Go local dev compatibility patch applied." -ForegroundColor Green
} else {
    Write-Host "Expo Go local dev compatibility patch already applied." -ForegroundColor Yellow
}
