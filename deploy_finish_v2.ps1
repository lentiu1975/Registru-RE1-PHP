$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== DEPLOY FINISH UPGRADE V2 ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off
cd /vamactasud.lentiu.ro
put -transfer=binary finish_upgrade_v2.php finish_upgrade_v2.php
exit
"@

$winscp_script | Out-File -FilePath "temp_finish_v2.txt" -Encoding ASCII
& $WINSCP /script=temp_finish_v2.txt
Remove-Item temp_finish_v2.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host ""
Write-Host "Acceseaza:" -ForegroundColor Cyan
Write-Host "http://vamactasud.lentiu.ro/finish_upgrade_v2.php" -ForegroundColor White
Write-Host ""
Write-Host "Aceasta versiune SMART:" -ForegroundColor Yellow
Write-Host "  - Verifica mai intai ce campuri exista" -ForegroundColor White
Write-Host "  - Adauga DOAR ce lipseste" -ForegroundColor White
Write-Host "  - NU se mai blocheaza pe duplicate columns!" -ForegroundColor Green
