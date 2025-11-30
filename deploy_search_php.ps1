$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== DEPLOY SEARCH.PHP WITH SHIP DATA ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off

lcd C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP
cd /vamactasud.lentiu.ro/api
put -transfer=binary api\search.php search.php

exit
"@

$winscp_script | Out-File -FilePath "temp_searchphp.txt" -Encoding ASCII
& $WINSCP /script=temp_searchphp.txt
Remove-Item temp_searchphp.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host "search.php actualizat - returneaza ship_name, ship_flag din baza de date" -ForegroundColor Cyan
