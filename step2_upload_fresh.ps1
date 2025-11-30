# PASUL 2: Upload fișiere PHP fresh

$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host " UPLOAD FIȘIERE PHP NOI" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$winscp_script = @"
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
put .htaccess

mkdir config
cd config
lcd config
put database.php
cd ..
lcd ..

mkdir api
cd api
lcd api
put manifests.php
put search.php
put import.php
cd ..
lcd ..

mkdir includes
cd includes
lcd includes
put functions.php
put auth.php
cd ..
lcd ..

mkdir assets
cd assets
mkdir css
mkdir js
cd css
lcd assets/css
put style.css
cd ..
lcd ../..
cd js
lcd assets/js
put app.js
cd ../..
lcd ../..

mkdir uploads
chmod 755 uploads

mkdir images
cd images
mkdir containere
mkdir drapele
mkdir nave

exit
"@

$winscp_script | Out-File -FilePath "temp_upload.txt" -Encoding ASCII

Write-Host "Upload în curs..." -ForegroundColor Yellow
& $WINSCP /script=temp_upload.txt

Remove-Item temp_upload.txt

Write-Host ""
Write-Host "✓ Fișiere uploadate cu succes!" -ForegroundColor Green
Write-Host ""
Write-Host "Testați: https://vama.lentiu.ro" -ForegroundColor Cyan
Write-Host ""
