$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== DEPLOY CONFIG & INCLUDES ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off

cd /vamactasud.lentiu.ro

# Upload config directory
mkdir config
cd config
lcd "C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP\config"
put -transfer=binary database.php
cd /vamactasud.lentiu.ro

# Upload includes directory
mkdir includes
cd includes
lcd "C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP\includes"
put -transfer=binary functions.php
cd /vamactasud.lentiu.ro

exit
"@

$winscp_script | Out-File -FilePath "temp_config.txt" -Encoding ASCII
& $WINSCP /script=temp_config.txt
Remove-Item temp_config.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host ""
Write-Host "Fișiere uploadate:" -ForegroundColor Yellow
Write-Host "  - config/database.php" -ForegroundColor White
Write-Host "  - includes/functions.php" -ForegroundColor White
