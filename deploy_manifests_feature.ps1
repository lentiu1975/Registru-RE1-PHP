$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== DEPLOY MANIFESTS FEATURE ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off

cd /vamactasud.lentiu.ro

# Upload API files
mkdir api
cd api
mkdir manifests
cd manifests
lcd "C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP\api\manifests"
put -transfer=binary list.php
put -transfer=binary details.php
put -transfer=binary delete.php
put -transfer=binary export.php
cd /vamactasud.lentiu.ro

# Upload JavaScript
mkdir assets
cd assets
mkdir js
cd js
lcd "C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP\assets\js"
put -transfer=binary manifest-management.js
cd /vamactasud.lentiu.ro

# Upload admin_new.php actualizat
lcd "C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP"
put -transfer=binary admin_new.php

exit
"@

$winscp_script | Out-File -FilePath "temp_manifests.txt" -Encoding ASCII
& $WINSCP /script=temp_manifests.txt
Remove-Item temp_manifests.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host ""
Write-Host "Fișiere uploadate:" -ForegroundColor Yellow
Write-Host "  - api/manifests/list.php" -ForegroundColor White
Write-Host "  - api/manifests/details.php" -ForegroundColor White
Write-Host "  - api/manifests/delete.php" -ForegroundColor White
Write-Host "  - api/manifests/export.php" -ForegroundColor White
Write-Host "  - assets/js/manifest-management.js" -ForegroundColor White
Write-Host ""
Write-Host "Acum poți testa Vizualizare Manifeste:" -ForegroundColor Cyan
Write-Host "http://vamactasud.lentiu.ro/admin_new.php" -ForegroundColor White
Write-Host ""
Write-Host "Click pe tab-ul 'Manifeste' din meniul lateral" -ForegroundColor Yellow
