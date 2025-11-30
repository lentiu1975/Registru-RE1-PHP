$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== DEPLOY CENTERED INFO BAR ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off

lcd C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP

cd /vamactasud.lentiu.ro
put -transfer=binary index.php index.php

cd assets/css
put -transfer=binary assets\css\search-style.css search-style.css

exit
"@

$winscp_script | Out-File -FilePath "temp_centered.txt" -Encoding ASCII
& $WINSCP /script=temp_centered.txt
Remove-Item temp_centered.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host "Info bar centrat - butonul Acasă nu mai e prea în stânga" -ForegroundColor Cyan
