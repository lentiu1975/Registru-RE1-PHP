$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== CREARE FOLDERE SI DEPLOY ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off

# Cream folderele necesare
cd public_html/vama
mkdir config
mkdir includes
mkdir api
mkdir assets
cd assets
mkdir js
mkdir css
cd ../..

# ROOT files
cd public_html/vama
put index.php
put admin.php

# Config
cd config
put -transfer=binary config\database.php database.php
cd ..

# Includes
cd includes
put -transfer=binary includes\functions.php functions.php
put -transfer=binary includes\auth.php auth.php
cd ..

# API
cd api
put -transfer=binary api\search.php search.php
put -transfer=binary api\test.php test.php
cd ..

# Assets JS
cd assets/js
put -transfer=binary assets\js\search-app.js search-app.js
cd ../../..

exit
"@

$winscp_script | Out-File -FilePath "temp_fix.txt" -Encoding ASCII
& $WINSCP /script=temp_fix.txt
Remove-Item temp_fix.txt

Write-Host ""
Write-Host "==== GATA! ====" -ForegroundColor Green
Write-Host "Test: http://vamactasud.lentiu.ro/api/test.php" -ForegroundColor Cyan
