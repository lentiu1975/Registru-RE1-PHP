$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"
$LOCAL_PATH = "C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP"

Write-Host "==== DEPLOY SHIPS FEATURE ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off

# Main files
cd /vamactasud.lentiu.ro
lcd "${LOCAL_PATH}"
put -transfer=binary admin_new.php

# API files
cd /vamactasud.lentiu.ro/api
lcd "${LOCAL_PATH}\api"
put -transfer=binary ships.php

exit
"@

$winscp_script | Out-File -FilePath "temp_ships_deploy.txt" -Encoding ASCII
& $WINSCP /script=temp_ships_deploy.txt
Remove-Item temp_ships_deploy.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host "Modificari:" -ForegroundColor Yellow
Write-Host "- admin_new.php - sectiune Nave completa"
Write-Host "- api/ships.php - API CRUD pentru nave"
Write-Host ""
Write-Host "Functionalitati noi:" -ForegroundColor Cyan
Write-Host "- Lista nave cu filtre (Cu poza/Fara poza)"
Write-Host "- Cautare nave"
Write-Host "- Adaugare/Editare/Stergere nave"
Write-Host "- Upload imagine nava"
