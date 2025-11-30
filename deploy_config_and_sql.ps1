# Deploy config actualizat + database.sql pentru import

$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"

Write-Host ""
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "  DEPLOY CONFIG + DATABASE SQL" -ForegroundColor White
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""

# 1. Upload config
Write-Host "1. Upload config actualizat..." -ForegroundColor Yellow

$winscp_config = @"
open ftp://$SERVER_USER`:$SERVER_PASS@$SERVER_IP/
option batch continue
option confirm off
option transfer binary

cd vamactasud.lentiu.ro/config

put config\database.php

exit
"@

$winscp_config | Out-File -FilePath "temp_cfg.txt" -Encoding ASCII
& "C:\Program Files (x86)\WinSCP\WinSCP.com" /script=temp_cfg.txt
Remove-Item temp_cfg.txt

Write-Host "   Config uploaded!" -ForegroundColor Green

# 2. Upload database.sql
Write-Host "2. Upload database.sql..." -ForegroundColor Yellow

$winscp_sql = @"
open ftp://$SERVER_USER`:$SERVER_PASS@$SERVER_IP/
option batch continue
option confirm off
option transfer binary

cd vamactasud.lentiu.ro

put database.sql

exit
"@

$winscp_sql | Out-File -FilePath "temp_sql.txt" -Encoding ASCII
& "C:\Program Files (x86)\WinSCP\WinSCP.com" /script=temp_sql.txt
Remove-Item temp_sql.txt

Write-Host "   database.sql uploaded!" -ForegroundColor Green

Write-Host ""
Write-Host "==========================================" -ForegroundColor Green
Write-Host "  UPLOAD COMPLET!" -ForegroundColor White
Write-Host "==========================================" -ForegroundColor Green
Write-Host ""
Write-Host "URMEAZA:" -ForegroundColor Yellow
Write-Host ""
Write-Host "1. Deschide phpMyAdmin in cPanel" -ForegroundColor White
Write-Host "2. Selecteaza database: lentiuro_vamactasud" -ForegroundColor Cyan
Write-Host "3. Click tab-ul 'Import'" -ForegroundColor White
Write-Host "4. Click 'Choose File' -> selecteaza: database.sql" -ForegroundColor White
Write-Host "   (fisierul este pe server in /vamactasud.lentiu.ro/database.sql)" -ForegroundColor Gray
Write-Host "5. Click 'Go' (jos de tot)" -ForegroundColor White
Write-Host ""
Write-Host "SAU mai simplu:" -ForegroundColor Yellow
Write-Host "Upload local database.sql din:" -ForegroundColor White
Write-Host "  C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP\database.sql" -ForegroundColor Cyan
Write-Host ""
Write-Host "DUPA IMPORT, TESTEAZA:" -ForegroundColor Yellow
Write-Host "  https://vamactasud.lentiu.ro/login.php" -ForegroundColor Cyan
Write-Host ""
Write-Host "Login:" -ForegroundColor Yellow
Write-Host "  User: admin" -ForegroundColor White
Write-Host "  Pass: admin123" -ForegroundColor White
Write-Host ""
