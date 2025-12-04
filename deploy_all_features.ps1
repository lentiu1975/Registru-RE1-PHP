$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== DEPLOY ALL REMAINING FEATURES ====" -ForegroundColor Cyan
Write-Host "7. Template-uri Import" -ForegroundColor White
Write-Host "8. Import Excel" -ForegroundColor White
Write-Host "9. Export Date" -ForegroundColor White
Write-Host "10. Log-uri Import" -ForegroundColor White
Write-Host ""

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off

cd /vamactasud.lentiu.ro
lcd "C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP"
put -transfer=binary admin_new.php

cd /vamactasud.lentiu.ro/api
lcd "C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP\api"
put -transfer=binary import_templates.php
put -transfer=binary import_logs.php
put -transfer=binary export.php

exit
"@

$winscp_script | Out-File -FilePath "temp_all.txt" -Encoding ASCII
& $WINSCP /script=temp_all.txt
Remove-Item temp_all.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host ""
Write-Host "TOATE FUNCTIONALITATILE IMPLEMENTATE:" -ForegroundColor Cyan
Write-Host "1. Manifeste - vizualizare si cautare" -ForegroundColor White
Write-Host "2. Import Excel - deja functional" -ForegroundColor White
Write-Host "3. Utilizatori - CRUD complet" -ForegroundColor White
Write-Host "4. Ani Baze Date - creare/activare/stergere" -ForegroundColor White
Write-Host "5. Pavilioane - CRUD complet" -ForegroundColor White
Write-Host "6. Tipuri Containere - CRUD complet" -ForegroundColor White
Write-Host "7. Template-uri Import - CRUD cu mapare coloane" -ForegroundColor White
Write-Host "8. Export Date - CSV per manifest" -ForegroundColor White
Write-Host "9. Log-uri Import - istoric importuri" -ForegroundColor White
Write-Host ""
Write-Host "Testeaza: http://vamactasud.lentiu.ro/admin_new.php" -ForegroundColor Yellow
Write-Host "CTRL+F5 pentru refresh complet!" -ForegroundColor Yellow
