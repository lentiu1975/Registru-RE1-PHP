$SERVER_IP = '185.246.123.91'
$SERVER_USER = 'lentiuro'
$SERVER_PASS = 'zA5P7lg1l2'
$WINSCP = 'C:\Program Files (x86)\WinSCP\WinSCP.com'

Write-Host "==== DEPLOY COMPANIES & USER FILTERS ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off
cd /vamactasud.lentiu.ro
lcd "C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP"
put -transfer=binary admin_new.php
cd /vamactasud.lentiu.ro/api
lcd "C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP\api"
put -transfer=binary companies.php
put -transfer=binary users.php
exit
"@

$winscp_script | Out-File -FilePath 'temp_deploy.txt' -Encoding ASCII
& $WINSCP /script=temp_deploy.txt
Remove-Item temp_deploy.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host ""
Write-Host "Functionalitati noi:" -ForegroundColor Yellow
Write-Host "- Gestionare companii (adaugare din formularul utilizator)" -ForegroundColor White
Write-Host "- Filtre utilizatori dupa companie" -ForegroundColor White
Write-Host "- Filtre utilizatori dupa ultima accesare" -ForegroundColor White
Write-Host "  (azi, ieri, 2 zile, 3 zile, 1 saptamana, 1 luna, 3 luni, 6 luni, 1 an)" -ForegroundColor White
