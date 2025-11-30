$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== DEPLOY CHECK UPGRADE STATUS ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off
cd /vamactasud.lentiu.ro
put -transfer=binary check_upgrade_status.php check_upgrade_status.php
exit
"@

$winscp_script | Out-File -FilePath "temp_check.txt" -Encoding ASCII
& $WINSCP /script=temp_check.txt
Remove-Item temp_check.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host ""
Write-Host "Verifică statusul upgrade:" -ForegroundColor Cyan
Write-Host "http://vamactasud.lentiu.ro/check_upgrade_status.php" -ForegroundColor White
