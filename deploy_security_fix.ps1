$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== DEPLOY SECURITY FIX ====" -ForegroundColor Cyan
Write-Host "1. index.php - necesita autentificare" -ForegroundColor White
Write-Host "2. admin_new.php - doar pentru admin" -ForegroundColor White
Write-Host "3. api/search.php - necesita autentificare" -ForegroundColor White
Write-Host ""

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off

cd /vamactasud.lentiu.ro
lcd "C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP"
put -transfer=binary index.php
put -transfer=binary admin_new.php
put -transfer=binary admin.php
put -transfer=binary login.php

cd /vamactasud.lentiu.ro/api
lcd "C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP\api"
put -transfer=binary search.php

exit
"@

$winscp_script | Out-File -FilePath "temp_security.txt" -Encoding ASCII
& $WINSCP /script=temp_security.txt
Remove-Item temp_security.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host ""
Write-Host "SECURITATE APLICATA:" -ForegroundColor Cyan
Write-Host "- index.php acum necesita autentificare" -ForegroundColor White
Write-Host "- admin_new.php accesibil doar pentru admini" -ForegroundColor White
Write-Host "- Utilizatorii non-admin sunt redirectionati la index.php" -ForegroundColor White
Write-Host ""
Write-Host "Testeaza: http://vamactasud.lentiu.ro/" -ForegroundColor Yellow
