$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== DEPLOY IMPORT EXCEL FEATURE ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off

cd /vamactasud.lentiu.ro

# Upload API files
cd api
lcd api
put -transfer=binary import_excel.php
put -transfer=binary get_templates.php
cd ..
lcd ..

# Upload JavaScript
cd assets/js
lcd assets/js
put -transfer=binary import-excel.js
cd ../..
lcd ../..

# Upload admin_new.php actualizat
put -transfer=binary admin_new.php

exit
"@

$winscp_script | Out-File -FilePath "temp_import.txt" -Encoding ASCII
& $WINSCP /script=temp_import.txt
Remove-Item temp_import.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host ""
Write-Host "Fișiere uploadate:" -ForegroundColor Yellow
Write-Host "  - api/import_excel.php" -ForegroundColor White
Write-Host "  - api/get_templates.php" -ForegroundColor White
Write-Host "  - assets/js/import-excel.js" -ForegroundColor White
Write-Host ""
Write-Host "Acum poți testa Import Excel:" -ForegroundColor Cyan
Write-Host "http://vamactasud.lentiu.ro/admin_new.php" -ForegroundColor White
Write-Host ""
Write-Host "Click pe tab-ul 'Import Excel' din meniul lateral" -ForegroundColor Yellow
