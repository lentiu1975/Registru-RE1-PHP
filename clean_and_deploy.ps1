# CLEANUP COMPLET SERVER + DEPLOYMENT FRESH
# Șterge TOT din /public_html/vama și face deployment curat

$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "========================================" -ForegroundColor Red
Write-Host " CLEANUP COMPLET + DEPLOYMENT FRESH" -ForegroundColor Red
Write-Host "========================================" -ForegroundColor Red
Write-Host ""

# Verifică WinSCP
if (-Not (Test-Path $WINSCP)) {
    Write-Host "EROARE: WinSCP nu este instalat!" -ForegroundColor Red
    exit 1
}

Write-Host "[PASUL 1/3] ȘTERGERE COMPLETĂ FOLDER VAMA..." -ForegroundColor Yellow
Write-Host ""

# Script WinSCP pentru ștergere completă
$delete_script = @"
open ftp://$SERVER_USER`:$SERVER_PASS@$SERVER_IP/
option batch continue
option confirm off

# Șterge TOT din vama
cd public_html
rm -r vama

# Creează vama folder proaspăt gol
mkdir vama

exit
"@

$delete_script | Out-File -FilePath "temp_delete.txt" -Encoding ASCII

Write-Host "Ștergere în curs..." -ForegroundColor Red
& $WINSCP /script=temp_delete.txt

Remove-Item temp_delete.txt

Write-Host ""
Write-Host "✓ Folder vama șters complet!" -ForegroundColor Green
Write-Host ""

Start-Sleep -Seconds 2

Write-Host "[PASUL 2/3] UPLOAD FIȘIERE PHP NOI..." -ForegroundColor Yellow
Write-Host ""

# Script WinSCP pentru upload complet
$upload_script = @"
open ftp://$SERVER_USER`:$SERVER_PASS@$SERVER_IP/
option batch continue
option confirm off
option transfer binary

cd public_html/vama

# Upload fișiere principale
lcd "$PWD"
put -delete index.php
put -delete admin.php
put -delete login.php
put -delete logout.php
put -delete database.sql
put -delete composer.json
put -delete .htaccess

# Creare directoare
mkdir config
mkdir api
mkdir includes
mkdir assets
mkdir uploads

# Upload config
cd config
lcd config
put -delete database.php
cd ..
lcd ..

# Upload API
cd api
lcd api
put -delete manifests.php
put -delete search.php
put -delete import.php
cd ..
lcd ..

# Upload includes
cd includes
lcd includes
put -delete functions.php
put -delete auth.php
cd ..
lcd ..

# Upload assets
mkdir assets/css
mkdir assets/js

cd assets/css
lcd assets/css
put -delete style.css
cd ../..
lcd ../..

cd assets/js
lcd assets/js
put -delete app.js
cd ../..
lcd ../..

# Creare folder imagini (fără upload - prea mare)
mkdir images
cd images
mkdir containere
mkdir drapele
mkdir nave
cd ..

# Setare permisiuni
chmod 755 uploads

exit
"@

$upload_script | Out-File -FilePath "temp_upload.txt" -Encoding ASCII

Write-Host "Upload fișiere în curs..." -ForegroundColor Yellow
& $WINSCP /script=temp_upload.txt

Remove-Item temp_upload.txt

Write-Host ""
Write-Host "✓ Fișiere PHP uploadate!" -ForegroundColor Green
Write-Host ""

Start-Sleep -Seconds 1

Write-Host "[PASUL 3/3] VERIFICARE .htaccess..." -ForegroundColor Yellow
Write-Host ""

# Verifică că .htaccess există
$verify_script = @"
open ftp://$SERVER_USER`:$SERVER_PASS@$SERVER_IP/
option batch continue
option confirm off

cd public_html/vama
ls

exit
"@

$verify_script | Out-File -FilePath "temp_verify.txt" -Encoding ASCII
& $WINSCP /script=temp_verify.txt
Remove-Item temp_verify.txt

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host " DEPLOYMENT COMPLET FINALIZAT!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "URMĂTORII PAȘI:" -ForegroundColor Cyan
Write-Host ""
Write-Host "1. Testați: https://vama.lentiu.ro" -ForegroundColor White
Write-Host ""
Write-Host "2. Dacă TOT se descarcă:" -ForegroundColor Yellow
Write-Host "   - Accesați cPanel" -ForegroundColor Gray
Write-Host "   - Căutați 'Select PHP Version' sau 'MultiPHP'" -ForegroundColor Gray
Write-Host "   - Selectați PHP 7.4 sau 8.0 pentru vama.lentiu.ro" -ForegroundColor Gray
Write-Host "   - Salvați și testați din nou" -ForegroundColor Gray
Write-Host ""
Write-Host "3. După ce PHP funcționează:" -ForegroundColor White
Write-Host "   - Creați baza de date în cPanel/phpMyAdmin" -ForegroundColor Gray
Write-Host "   - Importați database.sql" -ForegroundColor Gray
Write-Host "   - Editați config/database.php cu parola" -ForegroundColor Gray
Write-Host ""
Write-Host "NOTĂ: Imaginile NU au fost uploadate (prea mari)" -ForegroundColor Yellow
Write-Host "      Rulați .\deploy_images.ps1 după ce aplicația funcționează" -ForegroundColor Yellow
Write-Host ""
