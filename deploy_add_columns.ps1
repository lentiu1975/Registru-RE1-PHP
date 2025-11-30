$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== DEPLOY ADD MISSING COLUMNS ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off
cd /vamactasud.lentiu.ro
lcd "C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP"
put -transfer=binary add_missing_columns.php
exit
"@

$winscp_script | Out-File -FilePath "temp_add_cols.txt" -Encoding ASCII
& $WINSCP /script=temp_add_cols.txt
Remove-Item temp_add_cols.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host ""
Write-Host "ACCESEAZA:" -ForegroundColor Yellow
Write-Host "http://vamactasud.lentiu.ro/add_missing_columns.php" -ForegroundColor White
Write-Host ""
Write-Host "Scriptul va adauga coloanele lipsă și va popula datele automat!" -ForegroundColor Cyan
