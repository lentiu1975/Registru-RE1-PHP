$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== DEPLOY CU PATH-URI ABSOLUTE ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off

# Nivel ROOT - public_html/vama
cd /public_html/vama
put -transfer=binary index.php
put -transfer=binary admin.php

# API folder
cd /public_html/vama/api
put -transfer=binary api\search.php
put -transfer=binary api\test.php

# Config folder
cd /public_html/vama/config
put -transfer=binary config\database.php

# Includes folder
cd /public_html/vama/includes
put -transfer=binary includes\functions.php
put -transfer=binary includes\auth.php

# Assets JS folder
cd /public_html/vama/assets/js
put -transfer=binary assets\js\search-app.js

# Assets CSS folder
cd /public_html/vama/assets/css
put -transfer=binary assets\css\search-style.css

exit
"@

$winscp_script | Out-File -FilePath "temp_abs.txt" -Encoding ASCII
& $WINSCP /script=temp_abs.txt
Remove-Item temp_abs.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host "Testeaza:" -ForegroundColor Yellow
Write-Host "  - Site: http://vamactasud.lentiu.ro" -ForegroundColor Cyan
Write-Host "  - API Test: http://vamactasud.lentiu.ro/api/test.php" -ForegroundColor Cyan
Write-Host "  - Search API: http://vamactasud.lentiu.ro/api/search.php?q=SUDU" -ForegroundColor Cyan
