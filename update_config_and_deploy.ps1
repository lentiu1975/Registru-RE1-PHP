# Actualizeaza config/database.php cu credentialele de productie si face deploy

param(
    [Parameter(Mandatory=$true)]
    [string]$DbName,

    [Parameter(Mandatory=$true)]
    [string]$DbUser,

    [Parameter(Mandatory=$true)]
    [string]$DbPass
)

$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"

Write-Host ""
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "  ACTUALIZARE CONFIG + DEPLOY" -ForegroundColor White
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Database: $DbName" -ForegroundColor Yellow
Write-Host "User: $DbUser" -ForegroundColor Yellow
Write-Host ""

# Citeste fisierul config actual
$configPath = "config\database.php"
$configContent = Get-Content $configPath -Raw

# Înlocuiește credențialele de productie
$configContent = $configContent -replace "define\('DB_HOST_PROD', 'localhost'\);", "define('DB_HOST_PROD', 'localhost');"
$configContent = $configContent -replace "define\('DB_USER_PROD', ''\);", "define('DB_USER_PROD', '$DbUser');"
$configContent = $configContent -replace "define\('DB_PASS_PROD', ''\);", "define('DB_PASS_PROD', '$DbPass');"
$configContent = $configContent -replace "define\('DB_NAME_PROD', ''\);", "define('DB_NAME_PROD', '$DbName');"

# Salvează fisierul actualizat
$configContent | Out-File -FilePath $configPath -Encoding UTF8 -NoNewline

Write-Host "Config actualizat local!" -ForegroundColor Green
Write-Host ""

# Upload config pe server
Write-Host "Upload config pe server..." -ForegroundColor Yellow

$winscp = @"
open ftp://$SERVER_USER`:$SERVER_PASS@$SERVER_IP/
option batch continue
option confirm off
option transfer binary

cd vamactasud.lentiu.ro/config

put config\database.php

exit
"@

$winscp | Out-File -FilePath "temp_config_update.txt" -Encoding ASCII
& "C:\Program Files (x86)\WinSCP\WinSCP.com" /script=temp_config_update.txt
Remove-Item temp_config_update.txt

Write-Host ""
Write-Host "==========================================" -ForegroundColor Green
Write-Host "  CONFIG ACTUALIZAT SI DEPLOYED!" -ForegroundColor White
Write-Host "==========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Testeaza aplicatia:" -ForegroundColor Yellow
Write-Host "  https://vamactasud.lentiu.ro/login.php" -ForegroundColor Cyan
Write-Host ""
Write-Host "Login default:" -ForegroundColor Yellow
Write-Host "  User: admin" -ForegroundColor White
Write-Host "  Pass: admin123" -ForegroundColor White
Write-Host ""
Write-Host "IMPORTANT: Schimba parola adminului dupa login!" -ForegroundColor Red
Write-Host ""
