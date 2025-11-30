$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== DEPLOY COMPLET PHP APP ====" -ForegroundColor Cyan
Write-Host ""

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off

# ROOT files
cd public_html/vama
put index.php
put admin.php
put login.php
put logout.php

# Config
cd config
put config\database.php database.php
cd ..

# Includes
cd includes
put includes\functions.php functions.php
put includes\auth.php auth.php
cd ..

# API
cd api
put api\search.php search.php
put api\test.php test.php
put api\manifests.php manifests.php
put api\import.php import.php
cd ..

# Assets JS
cd assets/js
put assets\js\search-app.js search-app.js
cd ../..

exit
"@

$winscp_script | Out-File -FilePath "temp_deploy.txt" -Encoding ASCII
& $WINSCP /script=temp_deploy.txt
Remove-Item temp_deploy.txt

Write-Host ""
Write-Host "==== DEPLOY COMPLET! ====" -ForegroundColor Green
Write-Host "Site: http://vamactasud.lentiu.ro" -ForegroundColor Cyan
Write-Host "Test: http://vamactasud.lentiu.ro/api/test.php" -ForegroundColor Yellow
