$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== DEPLOY SUMMARY NUMBER SPLIT ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off

cd /vamactasud.lentiu.ro/assets/js
put -transfer=binary assets\js\search-app.js search-app.js

exit
"@

$winscp_script | Out-File -FilePath "temp_summary.txt" -Encoding ASCII
& $WINSCP /script=temp_summary.txt
Remove-Item temp_summary.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host "Numere sumara separate pe randuri (split by ; or ,)" -ForegroundColor Cyan
