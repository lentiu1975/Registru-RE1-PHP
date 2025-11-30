# Deploy PHP la public_html/vama (Document Root corect)

$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host ""
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "  DEPLOY PHP LA public_html/vama" -ForegroundColor White
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""

# Upload fisiere principale
Write-Host "1. Uploading fisiere principale..." -ForegroundColor Yellow

$winscp_main = @"
open ftp://$SERVER_USER`:$SERVER_PASS@$SERVER_IP/
option batch continue
option confirm off
option transfer binary

cd public_html/vama

put index.php
put admin.php
put login.php
put logout.php
put database.sql
put composer.json
put test.php

exit
"@

$winscp_main | Out-File -FilePath "temp_main.txt" -Encoding ASCII
& "C:\Program Files (x86)\WinSCP\WinSCP.com" /script=temp_main.txt
Remove-Item temp_main.txt

# Upload config
Write-Host "2. Uploading config..." -ForegroundColor Yellow

$winscp_config = @"
open ftp://$SERVER_USER`:$SERVER_PASS@$SERVER_IP/
option batch continue
option confirm off
option transfer binary

cd public_html/vama
mkdir config
cd config

put config\database.php

exit
"@

$winscp_config | Out-File -FilePath "temp_config.txt" -Encoding ASCII
& "C:\Program Files (x86)\WinSCP\WinSCP.com" /script=temp_config.txt
Remove-Item temp_config.txt

# Upload includes
Write-Host "3. Uploading includes..." -ForegroundColor Yellow

$winscp_includes = @"
open ftp://$SERVER_USER`:$SERVER_PASS@$SERVER_IP/
option batch continue
option confirm off
option transfer binary

cd public_html/vama
mkdir includes
cd includes

put includes\auth.php
put includes\functions.php

exit
"@

$winscp_includes | Out-File -FilePath "temp_includes.txt" -Encoding ASCII
& "C:\Program Files (x86)\WinSCP\WinSCP.com" /script=temp_includes.txt
Remove-Item temp_includes.txt

# Upload API
Write-Host "4. Uploading API..." -ForegroundColor Yellow

$winscp_api = @"
open ftp://$SERVER_USER`:$SERVER_PASS@$SERVER_IP/
option batch continue
option confirm off
option transfer binary

cd public_html/vama
mkdir api
cd api

put api\manifests.php
put api\search.php
put api\import.php

exit
"@

$winscp_api | Out-File -FilePath "temp_api.txt" -Encoding ASCII
& "C:\Program Files (x86)\WinSCP\WinSCP.com" /script=temp_api.txt
Remove-Item temp_api.txt

# Upload assets CSS
Write-Host "5. Uploading CSS..." -ForegroundColor Yellow

$winscp_css = @"
open ftp://$SERVER_USER`:$SERVER_PASS@$SERVER_IP/
option batch continue
option confirm off
option transfer binary

cd public_html/vama
mkdir assets
cd assets
mkdir css
cd css

put assets\css\style.css

exit
"@

$winscp_css | Out-File -FilePath "temp_css.txt" -Encoding ASCII
& "C:\Program Files (x86)\WinSCP\WinSCP.com" /script=temp_css.txt
Remove-Item temp_css.txt

# Create directories
Write-Host "6. Creating directories..." -ForegroundColor Yellow

$winscp_dirs = @"
open ftp://$SERVER_USER`:$SERVER_PASS@$SERVER_IP/
option batch continue
option confirm off

cd public_html/vama
mkdir uploads
mkdir images
cd images
mkdir containere
mkdir drapele
mkdir nave

exit
"@

$winscp_dirs | Out-File -FilePath "temp_dirs.txt" -Encoding ASCII
& "C:\Program Files (x86)\WinSCP\WinSCP.com" /script=temp_dirs.txt
Remove-Item temp_dirs.txt

Write-Host ""
Write-Host "=====================================" -ForegroundColor Green
Write-Host "  DEPLOY COMPLET!" -ForegroundColor White
Write-Host "=====================================" -ForegroundColor Green
Write-Host ""
Write-Host "Testeaza ACUM:" -ForegroundColor Yellow
Write-Host "  https://vama.lentiu.ro/test.php" -ForegroundColor Cyan
Write-Host ""
Write-Host "Ar trebui sa vezi:" -ForegroundColor Yellow
Write-Host "  PHP FUNCTIONEAZA! + phpinfo()" -ForegroundColor Green
Write-Host ""
