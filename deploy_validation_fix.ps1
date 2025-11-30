$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== UPLOAD VALIDARE MINIM 7 CARACTERE ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off

# Navigate to site root
cd /vamactasud.lentiu.ro

# Upload search.php cu validare
cd api
put -transfer=binary api\search.php search.php
cd ..

# Upload search-app.js cu validare
cd assets/js
put -transfer=binary assets\js\search-app.js search-app.js
cd ../..

exit
"@

$winscp_script | Out-File -FilePath "temp_validation.txt" -Encoding ASCII
& $WINSCP /script=temp_validation.txt
Remove-Item temp_validation.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host "Validare minim 7 caractere activata!" -ForegroundColor Cyan
Write-Host "Test: http://vamactasud.lentiu.ro" -ForegroundColor Yellow
