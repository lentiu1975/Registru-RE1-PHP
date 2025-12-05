$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"
$LOCAL_PATH = "C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP"

Write-Host "==== DEPLOY YEARS FEATURE ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off

# Main files
cd /vamactasud.lentiu.ro
lcd "${LOCAL_PATH}"
put -transfer=binary index.php
put -transfer=binary admin_new.php

# Includes
cd /vamactasud.lentiu.ro/includes
lcd "${LOCAL_PATH}\includes"
put -transfer=binary functions.php

# API files
cd /vamactasud.lentiu.ro/api
lcd "${LOCAL_PATH}\api"
put -transfer=binary database_years.php
put -transfer=binary import_excel.php
put -transfer=binary search.php

# JS files
cd /vamactasud.lentiu.ro/assets/js
lcd "${LOCAL_PATH}\assets\js"
put -transfer=binary search-app.js

exit
"@

$winscp_script | Out-File -FilePath "temp_years_deploy.txt" -Encoding ASCII
& $WINSCP /script=temp_years_deploy.txt
Remove-Item temp_years_deploy.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host "Modificari:" -ForegroundColor Yellow
Write-Host "- index.php - selector ani dinamic"
Write-Host "- admin_new.php - interfata Ani Baze Date imbunatatita"
Write-Host "- functions.php - functii helper pentru ani"
Write-Host "- database_years.php - API ani cu numar containere"
Write-Host "- import_excel.php - salvare in anul activ"
Write-Host "- search.php - filtrare dupa an"
Write-Host "- search-app.js - trimite year_id la cautare"
