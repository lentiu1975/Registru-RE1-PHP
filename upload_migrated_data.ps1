# Upload migrate_data.sql pe server

$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"

Write-Host ""
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "  UPLOAD DATE MIGRATE (3195 containere)" -ForegroundColor White
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""

$winscp = @"
open ftp://$SERVER_USER`:$SERVER_PASS@$SERVER_IP/
option batch continue
option confirm off
option transfer binary

cd vamactasud.lentiu.ro

put migrate_data.sql

exit
"@

$winscp | Out-File -FilePath "temp_migrate.txt" -Encoding ASCII
& "C:\Program Files (x86)\WinSCP\WinSCP.com" /script=temp_migrate.txt
Remove-Item temp_migrate.txt

Write-Host ""
Write-Host "==========================================" -ForegroundColor Green
Write-Host "  UPLOAD COMPLET!" -ForegroundColor White
Write-Host "==========================================" -ForegroundColor Green
Write-Host ""
Write-Host "URMEAZA - Import in phpMyAdmin:" -ForegroundColor Yellow
Write-Host ""
Write-Host "1. Deschide phpMyAdmin" -ForegroundColor White
Write-Host "2. Selecteaza: lentiuro_vamactasud" -ForegroundColor Cyan
Write-Host "3. Click 'Import'" -ForegroundColor White
Write-Host "4. Choose File -> migrate_data.sql" -ForegroundColor White
Write-Host "   (de pe server: /vamactasud.lentiu.ro/migrate_data.sql)" -ForegroundColor Gray
Write-Host "5. Click 'Go'" -ForegroundColor White
Write-Host ""
Write-Host "SAU upload local din:" -ForegroundColor Yellow
Write-Host "  C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP\migrate_data.sql" -ForegroundColor Cyan
Write-Host ""
Write-Host "DUPA IMPORT:" -ForegroundColor Yellow
Write-Host "  - 188 tipuri containere" -ForegroundColor Green
Write-Host "  - 2 nave" -ForegroundColor Green
Write-Host "  - 3195 containere!" -ForegroundColor Green
Write-Host ""
