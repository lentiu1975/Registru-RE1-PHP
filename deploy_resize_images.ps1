$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== DEPLOY RESIZED IMAGES (CSS + JS) ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off

lcd C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP

cd /vamactasud.lentiu.ro/assets/css
put -transfer=binary assets\css\search-style.css search-style.css

cd ../js
put -transfer=binary assets\js\search-app.js search-app.js

exit
"@

$winscp_script | Out-File -FilePath "temp_resize.txt" -Encoding ASCII
& $WINSCP /script=temp_resize.txt
Remove-Item temp_resize.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host "Poze container si nava redimensionate la max 300px" -ForegroundColor Cyan
