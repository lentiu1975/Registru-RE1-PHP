$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== DEPLOY CHECK COLUMNS ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off
cd /vamactasud.lentiu.ro
lcd "C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP"
put -transfer=binary check_and_add_columns.php
exit
"@

$winscp_script | Out-File -FilePath "temp_check_cols.txt" -Encoding ASCII
& $WINSCP /script=temp_check_cols.txt
Remove-Item temp_check_cols.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host ""
Write-Host "ACCESEAZA:" -ForegroundColor Yellow
Write-Host "http://vamactasud.lentiu.ro/check_and_add_columns.php" -ForegroundColor White
Write-Host ""
Write-Host "Vei vedea ce coloane lipsesc si SQL-ul pentru a le adauga!" -ForegroundColor Cyan
