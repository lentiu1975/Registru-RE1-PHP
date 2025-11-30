$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== DEPLOY CHECK TABLE STRUCTURE ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off
cd /vamactasud.lentiu.ro
lcd "C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP"
put -transfer=binary check_table_structure.php
exit
"@

$winscp_script | Out-File -FilePath "temp_check.txt" -Encoding ASCII
& $WINSCP /script=temp_check.txt
Remove-Item temp_check.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host ""
Write-Host "ACCESEAZA:" -ForegroundColor Yellow
Write-Host "http://vamactasud.lentiu.ro/check_table_structure.php" -ForegroundColor White
Write-Host ""
Write-Host "Vei vedea structura exacta a tabelelor si numele REALE ale coloanelor!" -ForegroundColor Cyan
