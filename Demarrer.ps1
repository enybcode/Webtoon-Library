param([switch]$SansNavigateur, [string]$PhpPath, [int]$Port = 8088)
$ErrorActionPreference = 'Stop'
$root = $PSScriptRoot
if (-not $PhpPath) {
    $PhpPath = Join-Path $root 'runtime\php\php.exe'
    if (-not (Test-Path -LiteralPath $PhpPath)) {
        $command = Get-Command php.exe -ErrorAction SilentlyContinue
        if (-not $command) { throw 'PHP introuvable. Voir INSTALLATION-LOCALE.md.' }
        $PhpPath = $command.Source
    }
}
$PhpPath = (Resolve-Path -LiteralPath $PhpPath).Path
foreach ($directory in @('data', 'logs')) {
    New-Item -ItemType Directory -Path (Join-Path $root $directory) -Force | Out-Null
}
$env:WEBTOON_DB_DRIVER = 'sqlite'
$env:WEBTOON_SQLITE_PATH = Join-Path $root 'data\webtoon_library.sqlite'
$url = "http://127.0.0.1:$Port"
$pidFile = Join-Path $root 'data\server.pid'
$existing = $null
if (Test-Path -LiteralPath $pidFile) {
    $existing = Get-Process -Id ([int](Get-Content -LiteralPath $pidFile)) -ErrorAction SilentlyContinue
    if ($existing -and $existing.Path -ne $PhpPath) { $existing = $null }
}
if (-not $existing) {
    if (Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue) {
        throw "Le port $Port est occupe par un autre programme."
    }
    $arguments = '-d session.save_path="' + (Join-Path $root 'data') + '" -d log_errors=1 -d display_errors=0 -d error_log="' + (Join-Path $root 'logs\php-errors.log') + '" -S 127.0.0.1:' + $Port + ' -t "' + $root + '" "' + (Join-Path $root 'router-local.php') + '"'
    $server = Start-Process -FilePath $PhpPath -ArgumentList $arguments -WorkingDirectory $root -WindowStyle Hidden -PassThru -RedirectStandardOutput (Join-Path $root 'logs\server.log') -RedirectStandardError (Join-Path $root 'logs\server-error.log')
    $server.Id | Set-Content -LiteralPath $pidFile
    Start-Sleep -Seconds 2
}
$response = Invoke-WebRequest "$url/connexion.php" -UseBasicParsing
if ($response.StatusCode -ne 200) { throw 'Le site ne repond pas.' }
if (-not $SansNavigateur) { Start-Process $url }
Write-Output "Webtoon Library : $url"
