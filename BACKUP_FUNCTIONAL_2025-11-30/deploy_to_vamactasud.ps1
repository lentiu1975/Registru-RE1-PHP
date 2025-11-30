$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== DEPLOY LA /vamactasud.lentiu.ro ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off

# Navigate to correct location
cd /vamactasud.lentiu.ro

# Create folder structure
mkdir api
mkdir config
mkdir includes
mkdir assets
cd assets
mkdir js
mkdir css
cd ..

# Upload ROOT files
put -transfer=binary index.php
put -transfer=binary admin.php
put -transfer=binary login.php
put -transfer=binary logout.php

# Upload API files
cd api
put -transfer=binary api\search.php search.php
put -transfer=binary api\test.php test.php
put -transfer=binary api\manifests.php manifests.php
put -transfer=binary api\import.php import.php
cd ..

# Upload config files
cd config
put -transfer=binary config\database.php database.php
cd ..

# Upload includes files
cd includes
put -transfer=binary includes\functions.php functions.php
put -transfer=binary includes\auth.php auth.php
cd ..

# Upload assets JS
cd assets/js
put -transfer=binary assets\js\search-app.js search-app.js
put -transfer=binary assets\js\admin-app.js admin-app.js
cd ../..

# Upload assets CSS
cd assets/css
put -transfer=binary assets\css\search-style.css search-style.css
put -transfer=binary assets\css\admin-style.css admin-style.css
cd ../..

exit
"@

$winscp_script | Out-File -FilePath "temp_vamactasud.txt" -Encoding ASCII
& $WINSCP /script=temp_vamactasud.txt
Remove-Item temp_vamactasud.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host "Testeaza:" -ForegroundColor Yellow
Write-Host "  - Site: http://vamactasud.lentiu.ro" -ForegroundColor Cyan
Write-Host "  - API Test: http://vamactasud.lentiu.ro/api/test.php" -ForegroundColor Cyan
Write-Host "  - Search: http://vamactasud.lentiu.ro/api/search.php?q=SUDU" -ForegroundColor Cyan
