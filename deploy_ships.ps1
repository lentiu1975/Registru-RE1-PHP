$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== DEPLOY SHIP INFORMATION ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off

cd /vamactasud.lentiu.ro
put -transfer=binary add_ship_fields.sql
put -transfer=binary migrate_with_ships.sql

cd assets/js
put -transfer=binary assets\js\search-app.js search-app.js

exit
"@

$winscp_script | Out-File -FilePath "temp_ships.txt" -Encoding ASCII
& $WINSCP /script=temp_ships.txt
Remove-Item temp_ships.txt

Write-Host ""
Write-Host "==== UPLOAD FINALIZAT ====" -ForegroundColor Green
Write-Host ""
Write-Host "ACUM in phpMyAdmin:" -ForegroundColor Yellow
Write-Host "1. Selecteaza baza de date 'lentiuro_vama'" -ForegroundColor Cyan
Write-Host "2. Import -> add_ship_fields.sql (adauga campuri ship_name, ship_flag)" -ForegroundColor Cyan
Write-Host "3. Import -> migrate_with_ships.sql (re-importa toate datele cu informatii nave)" -ForegroundColor Cyan
Write-Host ""
Write-Host "JavaScript actualizat - afiseaza nume nava, pavilion, poza navei" -ForegroundColor Green
