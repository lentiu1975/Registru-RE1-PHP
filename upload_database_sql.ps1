# Upload database.sql pentru import în phpMyAdmin

$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"

Write-Host ""
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "  UPLOAD database.sql" -ForegroundColor White
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""

$winscp = @"
open ftp://$SERVER_USER`:$SERVER_PASS@$SERVER_IP/
option batch continue
option confirm off
option transfer binary

cd vamactasud.lentiu.ro

put database.sql

exit
"@

$winscp | Out-File -FilePath "temp_db.txt" -Encoding ASCII
& "C:\Program Files (x86)\WinSCP\WinSCP.com" /script=temp_db.txt
Remove-Item temp_db.txt

Write-Host ""
Write-Host "==========================================" -ForegroundColor Green
Write-Host "  database.sql UPLOADED!" -ForegroundColor White
Write-Host "==========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Acum poti importa in phpMyAdmin:" -ForegroundColor Yellow
Write-Host "1. Deschide phpMyAdmin in cPanel" -ForegroundColor White
Write-Host "2. Selecteaza database-ul" -ForegroundColor White
Write-Host "3. Click Import" -ForegroundColor White
Write-Host "4. Choose File: database.sql" -ForegroundColor White
Write-Host "5. Click Go" -ForegroundColor White
Write-Host ""
