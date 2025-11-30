$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== UPLOAD SQL PARTS ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off

cd /vamactasud.lentiu.ro
mkdir sql_parts
cd sql_parts

lcd sql_parts
put -transfer=binary *.sql

exit
"@

$winscp_script | Out-File -FilePath "temp_parts.txt" -Encoding ASCII
& $WINSCP /script=temp_parts.txt
Remove-Item temp_parts.txt

Write-Host ""
Write-Host "==== UPLOAD FINALIZAT ====" -ForegroundColor Green
Write-Host ""
Write-Host "ACUM:" -ForegroundColor Yellow
Write-Host "1. Deschide phpMyAdmin din cPanel" -ForegroundColor Cyan
Write-Host "2. Selecteaza baza de date 'lentiuro_vama'" -ForegroundColor Cyan
Write-Host "3. Click 'Import' si importeaza fisierele in ordine:" -ForegroundColor Cyan
Write-Host ""
Write-Host "   PRIMUL: add_missing_columns.sql (deja uploadat)" -ForegroundColor White
Write-Host "   apoi: sql_parts/part_01_of_13.sql" -ForegroundColor White
Write-Host "   apoi: sql_parts/part_02_of_13.sql" -ForegroundColor White
Write-Host "   ... pana la part_13_of_13.sql" -ForegroundColor White
Write-Host ""
Write-Host "Fiecare fisier contine ~250 containere si se importa rapid!" -ForegroundColor Green
