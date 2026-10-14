$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent $MyInvocation.MyCommand.Path
$Mysqld = Join-Path $Root "runtime\mariadb\mariadb-11.4.13-winx64\bin\mysqld.exe"
$Config = Join-Path $Root "runtime\mariadb\mariadb-11.4.13-winx64\data\my.ini"

function Test-Port([int] $Port) {
    return [bool](Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue)
}

if (-not (Test-Port 3306)) {
    Start-Process -FilePath $Mysqld -ArgumentList @("--defaults-file=$Config", "stroymarket") -WindowStyle Hidden
    foreach ($try in 1..40) {
        Start-Sleep -Seconds 1
        if (Test-Port 3306) { break }
    }
    if (-not (Test-Port 3306)) {
        throw "База не поднялась на порту 3306"
    }
}

& "$env:windir\system32\inetsrv\appcmd.exe" start site "stroymarket-web" | Out-Null
Write-Output "Магазин: http://127.0.0.1:8090"
