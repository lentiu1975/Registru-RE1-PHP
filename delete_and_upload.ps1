# CLEANUP COMPLET + UPLOAD FRESH - fara diacritice

$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "========================================"
Write-Host " STERGERE COMPLETA + DEPLOYMENT FRESH"
Write-Host "========================================"
Write-Host ""

# PASUL 1: STERGERE
Write-Host "[1/2] Stergere folder vama complet..." -ForegroundColor Red

$delete = @"
open ftp://$SERVER_USER`:$SERVER_PASS@$SERVER_IP/
option batch continue
option confirm off
cd public_html
rm -r vama
mkdir vama
exit
"@

$delete | Out-File -FilePath "t1.txt" -Encoding ASCII
& $WINSCP /script=t1.txt
Remove-Item t1.txt

Write-Host "OK - Tot sters!" -ForegroundColor Green
Write-Host ""

# PASUL 2: UPLOAD
Write-Host "[2/2] Upload fisiere PHP noi..." -ForegroundColor Cyan

$upload = @"
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

$upload | Out-File -FilePath "t2.txt" -Encoding ASCII
& $WINSCP /script=t2.txt
Remove-Item t2.txt

Write-Host ""
Write-Host "========================================"
Write-Host " DEPLOYMENT COMPLET!" -ForegroundColor Green
Write-Host "========================================"
Write-Host ""
Write-Host "Testati: https://vama.lentiu.ro" -ForegroundColor Cyan
Write-Host ""
