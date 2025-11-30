$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== DEPLOY QUICK UPGRADE ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off
cd /vamactasud.lentiu.ro
put -transfer=binary quick_upgrade.php quick_upgrade.php
exit
"@

$winscp_script | Out-File -FilePath "temp_quick.txt" -Encoding ASCII
& $WINSCP /script=temp_quick.txt
Remove-Item temp_quick.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host "Accesează: http://vamactasud.lentiu.ro/quick_upgrade.php" -ForegroundColor Cyan
Write-Host ""
Write-Host "Acest script va adăuga DOAR câmpurile esențiale pentru login!" -ForegroundColor Yellow
