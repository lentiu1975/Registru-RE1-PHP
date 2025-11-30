$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== FIX MODAL: Deploy Admin + Manifest JS ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off

cd /vamactasud.lentiu.ro
lcd "C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP"
put -transfer=binary admin_new.php

cd /vamactasud.lentiu.ro/assets/js
lcd "C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP\assets\js"
put -transfer=binary manifest-management.js

exit
"@

$winscp_script | Out-File -FilePath "temp_modal_fix.txt" -Encoding ASCII
& $WINSCP /script=temp_modal_fix.txt
Remove-Item temp_modal_fix.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host ""
Write-Host "TESTEAZA:" -ForegroundColor Yellow
Write-Host "1. Deschide: http://vamactasud.lentiu.ro/admin_new.php" -ForegroundColor White
Write-Host "2. Apasa CTRL+F5 (Hard Refresh)" -ForegroundColor White
Write-Host "3. Mergi la tab Manifeste - vizualizare" -ForegroundColor White
Write-Host "4. Click pe butonul ochi (👁️)" -ForegroundColor White
Write-Host ""
Write-Host "Ar trebui sa se deschida modalul cu detalii!" -ForegroundColor Cyan
