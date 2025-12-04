$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== DEPLOY ANI + PAVILIOANE + CONTAINERE ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off

cd /vamactasud.lentiu.ro
lcd "C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP"
put -transfer=binary admin_new.php

cd /vamactasud.lentiu.ro/api
lcd "C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP\api"
put -transfer=binary database_years.php
put -transfer=binary pavilions.php
put -transfer=binary container_types.php

exit
"@

$winscp_script | Out-File -FilePath "temp_sections.txt" -Encoding ASCII
& $WINSCP /script=temp_sections.txt
Remove-Item temp_sections.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host ""
Write-Host "FUNCTIONALITATI ADAUGATE:" -ForegroundColor Cyan
Write-Host "4. Ani Baze Date - creare, activare, stergere" -ForegroundColor White
Write-Host "5. Pavilioane - CRUD complet cu imagini steaguri" -ForegroundColor White
Write-Host "6. Tipuri Containere - CRUD complet cu imagini" -ForegroundColor White
Write-Host ""
Write-Host "Testeaza: http://vamactasud.lentiu.ro/admin_new.php" -ForegroundColor Yellow
Write-Host "CTRL+F5 pentru refresh complet!" -ForegroundColor Yellow
