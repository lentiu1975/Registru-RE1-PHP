$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== UPLOAD DATE COMPLETE ====" -ForegroundColor Cyan
Write-Host "Pasul 1: Adaugare coloane noi in MySQL..." -ForegroundColor Yellow

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off

cd /vamactasud.lentiu.ro
put -transfer=binary add_missing_columns.sql
put -transfer=binary migrate_complete.sql

exit
"@

$winscp_script | Out-File -FilePath "temp_data.txt" -Encoding ASCII
& $WINSCP /script=temp_data.txt
Remove-Item temp_data.txt

Write-Host ""
Write-Host "Fisierele SQL au fost incarcate pe server!" -ForegroundColor Green
Write-Host ""
Write-Host "ACUM ruleaza urmatoarele comenzi in phpMyAdmin:" -ForegroundColor Yellow
Write-Host "1. Selecteaza baza de date 'lentiuro_vama'" -ForegroundColor Cyan
Write-Host "2. Import -> Alege fisierul 'add_missing_columns.sql'" -ForegroundColor Cyan
Write-Host "3. Import -> Alege fisierul 'migrate_complete.sql'" -ForegroundColor Cyan
Write-Host ""
Write-Host "Sau executa manual:" -ForegroundColor Yellow
Write-Host "  mysql -u lentiuro_vama -p lentiuro_vama < add_missing_columns.sql" -ForegroundColor White
Write-Host "  mysql -u lentiuro_vama -p lentiuro_vama < migrate_complete.sql" -ForegroundColor White
