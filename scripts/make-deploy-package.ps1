$ErrorActionPreference = 'Stop'

$root = Resolve-Path (Join-Path $PSScriptRoot '..')
$dist = Join-Path $root 'storage\deploy'
$package = Join-Path $dist ('novaone-deploy-' + (Get-Date -Format 'yyyyMMdd-HHmmss') + '.zip')
$stage = Join-Path ([System.IO.Path]::GetTempPath()) ('novaone-stage-' + [guid]::NewGuid().ToString('N'))

New-Item -ItemType Directory -Force -Path $dist | Out-Null
New-Item -ItemType Directory -Force -Path $stage | Out-Null

$includeRoots = @(
    'app',
    'config',
    'database',
    'public',
    'scripts'
)

$includeFiles = @(
    '.env.example',
    '.htaccess',
    'ARCHITECTURE.md',
    'DEPLOY.md',
    'index.php',
    'README.md',
    'router.php'
)

foreach ($dir in $includeRoots) {
    $source = Join-Path $root $dir
    if (Test-Path $source) {
        Copy-Item -LiteralPath $source -Destination (Join-Path $stage $dir) -Recurse -Force
    }
}

foreach ($file in $includeFiles) {
    $source = Join-Path $root $file
    if (Test-Path $source) {
        Copy-Item -LiteralPath $source -Destination (Join-Path $stage $file) -Force
    }
}

New-Item -ItemType Directory -Force -Path (Join-Path $stage 'storage') | Out-Null
foreach ($file in @('.htaccess', 'data.json', 'seed.php')) {
    $source = Join-Path $root ('storage\' + $file)
    if (Test-Path $source) {
        Copy-Item -LiteralPath $source -Destination (Join-Path $stage ('storage\' + $file)) -Force
    }
}

Remove-Item -LiteralPath (Join-Path $stage '.env') -Force -ErrorAction SilentlyContinue

Compress-Archive -Path (Join-Path $stage '*') -DestinationPath $package -Force
Remove-Item -LiteralPath $stage -Recurse -Force

Write-Host $package
