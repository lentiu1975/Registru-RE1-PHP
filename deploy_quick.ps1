$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off
cd /vamactasud.lentiu.ro/api
lcd "C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP\api"
put -transfer=binary latest_manifest.php
cd /vamactasud.lentiu.ro/assets/js
lcd "C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP\assets\js"
put -transfer=binary search-app.js
exit
"@

$winscp_script | Out-File -FilePath "temp.txt" -Encoding ASCII
& $WINSCP /script=temp.txt
Remove-Item temp.txt

Write-Host ""
Write-Host "Manifests list.php ACTUALIZAT!" -ForegroundColor Green
