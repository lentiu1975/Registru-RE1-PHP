$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== DEPLOY FORMAT MANIFEST COMPLET ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off

cd /vamactasud.lentiu.ro
put -transfer=binary add_manifest_fields.sql
put -transfer=binary migrate_full.sql

cd assets/js
put -transfer=binary assets\js\search-app.js search-app.js

exit
"@

$winscp_script | Out-File -FilePath "temp_manifest.txt" -Encoding ASCII
& $WINSCP /script=temp_manifest.txt
Remove-Item temp_manifest.txt

Write-Host ""
Write-Host "==== UPLOAD FINALIZAT ====" -ForegroundColor Green
Write-Host ""
Write-Host "ACUM in phpMyAdmin:" -ForegroundColor Yellow
Write-Host "1. Selecteaza baza de date 'lentiuro_vama'" -ForegroundColor Cyan
Write-Host "2. Import -> add_manifest_fields.sql (adauga campuri permit, position, request)" -ForegroundColor Cyan
Write-Host "3. Import -> migrate_full.sql (re-importa toate datele cu campuri noi)" -ForegroundColor Cyan
Write-Host ""
Write-Host "JavaScript actualizat - afiseaza format: manifest/permit/position/request - data" -ForegroundColor Green
