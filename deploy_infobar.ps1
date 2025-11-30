$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== DEPLOY INFO BAR (Acasă + Latest Manifest) ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off

lcd C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP

cd /vamactasud.lentiu.ro
put -transfer=binary index.php index.php

cd api
put -transfer=binary api\latest_manifest.php latest_manifest.php

cd ../assets/css
put -transfer=binary assets\css\search-style.css search-style.css

cd ../js
put -transfer=binary assets\js\search-app.js search-app.js

exit
"@

$winscp_script | Out-File -FilePath "temp_infobar.txt" -Encoding ASCII
& $WINSCP /script=temp_infobar.txt
Remove-Item temp_infobar.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host "Uploaded:" -ForegroundColor Cyan
Write-Host "  - index.php (with info bar HTML)" -ForegroundColor White
Write-Host "  - api/latest_manifest.php (backend API)" -ForegroundColor White
Write-Host "  - assets/css/search-style.css (info bar styles)" -ForegroundColor White
Write-Host "  - assets/js/search-app.js (load latest manifest)" -ForegroundColor White
