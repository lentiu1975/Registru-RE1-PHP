$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== UPLOAD SQL COMPLET (SIMPLIFIED) ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off

cd /vamactasud.lentiu.ro
put -transfer=binary add_missing_columns.sql
put -transfer=binary migrate_simple.sql

exit
"@

$winscp_script | Out-File -FilePath "temp_full.txt" -Encoding ASCII
& $WINSCP /script=temp_full.txt
Remove-Item temp_full.txt

Write-Host ""
Write-Host "==== UPLOAD FINALIZAT ====" -ForegroundColor Green
Write-Host ""
Write-Host "ACUM in phpMyAdmin:" -ForegroundColor Yellow
Write-Host "1. Selecteaza baza de date 'lentiuro_vama'" -ForegroundColor Cyan
Write-Host "2. Import -> add_missing_columns.sql (adauga coloanele)" -ForegroundColor Cyan
Write-Host "3. Import -> migrate_simple.sql (importa toate datele)" -ForegroundColor Cyan
Write-Host ""
Write-Host "Fisierul migrate_simple.sql contine 6390 randuri (3195 containere)" -ForegroundColor White
Write-Host "Structura SIMPLIFICATA - fara ship_name/ship_flag" -ForegroundColor White
