$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== DEPLOY COMPLETE UPDATE ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off

cd /vamactasud.lentiu.ro/api/manifests
lcd "C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP\api\manifests"
put -transfer=binary details.php

cd /vamactasud.lentiu.ro/api/entries
lcd "C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP\api\entries"
put -transfer=binary list_all.php
put -transfer=binary update.php

exit
"@

$winscp_script | Out-File -FilePath "temp_complete.txt" -Encoding ASCII
& $WINSCP /script=temp_complete.txt
Remove-Item temp_complete.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host ""
Write-Host "Files deployed:" -ForegroundColor Yellow
Write-Host "- api/manifests/details.php (updated columns)" -ForegroundColor White
Write-Host "- api/entries/list_all.php (new - list all entries)" -ForegroundColor White
Write-Host "- api/entries/update.php (new - update entry)" -ForegroundColor White
