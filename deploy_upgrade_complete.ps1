$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== DEPLOY UPGRADE COMPLET - Registru RE1 ====" -ForegroundColor Cyan
Write-Host ""

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off

lcd C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP

# Upload la root
cd /vamactasud.lentiu.ro

Write-Host "Uploading SQL upgrade files..." -ForegroundColor Yellow
put -transfer=binary upgrade_database.sql upgrade_database.sql
put -transfer=binary install_upgrade.php install_upgrade.php
put -transfer=binary run_upgrade.php run_upgrade.php

Write-Host "Uploading new admin panel..." -ForegroundColor Yellow
put -transfer=binary admin_new.php admin_new.php

Write-Host "Uploading documentation..." -ForegroundColor Yellow
put -transfer=binary UPGRADE_COMPLETE.md UPGRADE_COMPLETE.md

# Upload API-uri noi
Write-Host "Uploading new API files..." -ForegroundColor Yellow
cd api

put -transfer=binary api\users.php users.php
put -transfer=binary api\database_years.php database_years.php
put -transfer=binary api\pavilions.php pavilions.php
put -transfer=binary api\container_types.php container_types.php
put -transfer=binary api\import_templates.php import_templates.php

exit
"@

Write-Host "Starting FTP upload..." -ForegroundColor Green
$winscp_script | Out-File -FilePath "temp_upgrade_deploy.txt" -Encoding ASCII
& $WINSCP /script=temp_upgrade_deploy.txt

if ($LASTEXITCODE -eq 0) {
    Remove-Item temp_upgrade_deploy.txt
    Write-Host ""
    Write-Host "==== DEPLOY FINALIZAT CU SUCCES! ====" -ForegroundColor Green
    Write-Host ""
    Write-Host "Fișiere uploadate:" -ForegroundColor Cyan
    Write-Host "  ✓ upgrade_database.sql" -ForegroundColor White
    Write-Host "  ✓ install_upgrade.php" -ForegroundColor White
    Write-Host "  ✓ run_upgrade.php" -ForegroundColor White
    Write-Host "  ✓ admin_new.php" -ForegroundColor White
    Write-Host "  ✓ UPGRADE_COMPLETE.md" -ForegroundColor White
    Write-Host "  ✓ api/users.php" -ForegroundColor White
    Write-Host "  ✓ api/database_years.php" -ForegroundColor White
    Write-Host "  ✓ api/pavilions.php" -ForegroundColor White
    Write-Host "  ✓ api/container_types.php" -ForegroundColor White
    Write-Host "  ✓ api/import_templates.php" -ForegroundColor White
    Write-Host ""
    Write-Host "NEXT STEPS:" -ForegroundColor Yellow
    Write-Host "1. Accesează: http://vamactasud.lentiu.ro/install_upgrade.php" -ForegroundColor White
    Write-Host "2. Apasă butonul '🚀 Începe Upgrade'" -ForegroundColor White
    Write-Host "3. Verifică că toate comenzile SQL au rulat cu succes" -ForegroundColor White
    Write-Host "4. Accesează noul admin: http://vamactasud.lentiu.ro/admin_new.php" -ForegroundColor White
    Write-Host "5. Testează toate funcționalitățile" -ForegroundColor White
    Write-Host ""
} else {
    Write-Host ""
    Write-Host "==== EROARE LA DEPLOY! ====" -ForegroundColor Red
    Write-Host "Verifică conexiunea FTP și credențialele" -ForegroundColor Yellow
    Write-Host ""
}
