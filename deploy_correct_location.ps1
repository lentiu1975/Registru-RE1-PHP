# Deploy PHP la locatia CORECTA pentru subdomenul vama.lentiu.ro

$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host ""
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "  DEPLOY PHP LA LOCATIE CORECTA" -ForegroundColor White
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Subdomenul vama.lentiu.ro pointeaza la:" -ForegroundColor Yellow
Write-Host "/home/lentiuro/vama.lentiu.ro" -ForegroundColor Cyan
Write-Host ""
Write-Host "Uploading..." -ForegroundColor Green

$winscp = @"
open ftp://$SERVER_USER`:$SERVER_PASS@$SERVER_IP/
option batch continue
option confirm off
option transfer binary

# Upload in directorul CORECT pentru subdomeniu
cd vama.lentiu.ro

# Upload fisiere principale
put index.php
put admin.php
put login.php
put logout.php
put database.sql
put composer.json

# Upload test
put test.php

# Upload directoare
put -r config
put -r api
put -r includes
put -r assets
put -r uploads
put -r images

exit
"@

$winscp | Out-File -FilePath "temp_deploy_correct.txt" -Encoding ASCII
& "C:\Program Files (x86)\WinSCP\WinSCP.com" /script=temp_deploy_correct.txt
Remove-Item temp_deploy_correct.txt

Write-Host ""
Write-Host "=====================================" -ForegroundColor Green
Write-Host "  DEPLOY COMPLET!" -ForegroundColor White
Write-Host "=====================================" -ForegroundColor Green
Write-Host ""
Write-Host "Testeaza:" -ForegroundColor Yellow
Write-Host "  https://vama.lentiu.ro/test.php" -ForegroundColor Cyan
Write-Host ""
