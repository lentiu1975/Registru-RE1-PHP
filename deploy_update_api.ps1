$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== DEPLOY UPDATE API ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off

cd /vamactasud.lentiu.ro/api/entries
lcd "C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP\api\entries"
put -transfer=binary update.php

exit
"@

$winscp_script | Out-File -FilePath "temp_update_api.txt" -Encoding ASCII
& $WINSCP /script=temp_update_api.txt
Remove-Item temp_update_api.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host ""
Write-Host "API deploiat: api/entries/update.php" -ForegroundColor White
