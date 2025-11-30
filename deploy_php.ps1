# Script deployment pentru Registru Import RE1 - PHP
# Upload complet pe vama.lentiu.ro

$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "=====================================" -ForegroundColor Cyan
Write-Host " DEPLOY REGISTRU IMPORT RE1 - PHP" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""

# Verifică WinSCP
if (-Not (Test-Path $WINSCP)) {
    Write-Host "EROARE: WinSCP nu este instalat!" -ForegroundColor Red
    exit 1
}

Write-Host "[1/4] Creare script WinSCP..." -ForegroundColor Yellow

# Scriptul WinSCP
$winscp_script = @"
open ftp://$SERVER_USER`:$SERVER_PASS@$SERVER_IP/
option batch continue
option confirm off
option transfer binary

# Creare directoare pe server
cd public_html/vama
mkdir uploads
chmod 755 uploads

# Upload fișiere principale
put -delete index.php
put -delete admin.php
put -delete login.php
put -delete logout.php
put -delete database.sql
put -delete composer.json
put -delete INSTALL.md
put -delete README.md

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
cd assets
lcd assets

cd css
lcd css
put -delete style.css
cd ..
lcd ..

cd js
lcd js
put -delete app.js
cd ..
lcd ..

cd ..
lcd ..

# Upload imagini - IMPORTANT pentru performanță!
# Doar dacă nu există deja pe server

# Uncomment dacă vrei să încarci și imaginile (durează mai mult)
# cd images
# lcd images
# put -delete containere\*.*
# put -delete drapele\*.*
# put -delete nave\*.*
# cd ..
# lcd ..

exit
"@

Write-Host "[2/4] Salvare script temporar..." -ForegroundColor Yellow
$winscp_script | Out-File -FilePath "temp_deploy_php.txt" -Encoding ASCII

Write-Host "[3/4] Upload fișiere pe server..." -ForegroundColor Yellow
Write-Host ""
& $WINSCP /script=temp_deploy_php.txt

Write-Host ""
Write-Host "[4/4] Curățare fișiere temporare..." -ForegroundColor Yellow
Remove-Item temp_deploy_php.txt

Write-Host ""
Write-Host "=====================================" -ForegroundColor Green
Write-Host " DEPLOY FINALIZAT CU SUCCES!" -ForegroundColor Green
Write-Host "=====================================" -ForegroundColor Green
Write-Host ""

Write-Host "URMĂTORII PAȘI:" -ForegroundColor Cyan
Write-Host ""
Write-Host "1. Accesați cPanel → phpMyAdmin" -ForegroundColor White
Write-Host "   - Creați baza de date: lentiuro_vama" -ForegroundColor Gray
Write-Host "   - Importați fișierul database.sql" -ForegroundColor Gray
Write-Host ""
Write-Host "2. Editați config/database.php pe server" -ForegroundColor White
Write-Host "   - Setați parola bazei de date" -ForegroundColor Gray
Write-Host ""
Write-Host "3. Instalați PhpSpreadsheet (opțional pentru import Excel)" -ForegroundColor White
Write-Host "   - SSH: cd /home/lentiuro/public_html/vama && composer install" -ForegroundColor Gray
Write-Host "   - SAU descărcați manual în folder vendor/" -ForegroundColor Gray
Write-Host ""
Write-Host "4. Upload imagini (doar prima dată):" -ForegroundColor White
Write-Host "   - Rulați: .\deploy_images.ps1" -ForegroundColor Gray
Write-Host "   - SAU upload manual via FTP folderele: Containere, Drapele, Nave" -ForegroundColor Gray
Write-Host ""
Write-Host "5. Testați aplicația:" -ForegroundColor White
Write-Host "   - Public: https://vama.lentiu.ro" -ForegroundColor Gray
Write-Host "   - Admin: https://vama.lentiu.ro/admin.php" -ForegroundColor Gray
Write-Host "   - Login: admin / admin123" -ForegroundColor Gray
Write-Host ""
Write-Host "NOTĂ IMPORTANTĂ: Schimbați parola admin după prima autentificare!" -ForegroundColor Yellow
Write-Host ""
