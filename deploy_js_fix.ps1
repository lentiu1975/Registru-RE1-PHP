$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== DEPLOY JS FIX ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off

lcd C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP
cd /vamactasud.lentiu.ro/assets/js
put -transfer=binary assets\js\search-app.js search-app.js

exit
"@

$winscp_script | Out-File -FilePath "temp_jsfix.txt" -Encoding ASCII
& $WINSCP /script=temp_jsfix.txt
Remove-Item temp_jsfix.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host "JavaScript corectat - syntax error rezolvat" -ForegroundColor Cyan
