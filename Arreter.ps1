$root = $PSScriptRoot
$pidFile = Join-Path $root 'data\server.pid'
if (Test-Path $pidFile) {
    $server = Get-Process -Id ([int](Get-Content $pidFile)) -ErrorAction SilentlyContinue
    $processInfo = if ($server) { Get-CimInstance Win32_Process -Filter "ProcessId = $($server.Id)" }
    if ($server -and $processInfo.Name -eq 'php.exe' -and $processInfo.CommandLine.Contains((Join-Path $root 'router-local.php'))) {
        Stop-Process -Id $server.Id
    }
    Remove-Item -LiteralPath $pidFile
}
