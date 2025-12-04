$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== DEPLOY USERS MANAGEMENT ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off

cd /vamactasud.lentiu.ro
lcd "C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP"
put -transfer=binary admin_new.php

cd /vamactasud.lentiu.ro/api
lcd "C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP\api"
put -transfer=binary users.php

exit
"@

$winscp_script | Out-File -FilePath "temp_users.txt" -Encoding ASCII
& $WINSCP /script=temp_users.txt
Remove-Item temp_users.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host ""
Write-Host "Teste:" -ForegroundColor Yellow
Write-Host "1. http://vamactasud.lentiu.ro/admin_new.php" -ForegroundColor White
Write-Host "2. CTRL+F5" -ForegroundColor White
Write-Host "3. Click pe tab 'Utilizatori'" -ForegroundColor White
Write-Host ""
Write-Host "FUNCTIONALITATI:" -ForegroundColor Cyan
Write-Host "- Vizualizare lista utilizatori" -ForegroundColor White
Write-Host "- Creare utilizator nou" -ForegroundColor White
Write-Host "- Editare utilizator existent" -ForegroundColor White
Write-Host "- Stergere utilizator" -ForegroundColor White
Write-Host "- Toggle activ/inactiv" -ForegroundColor White
Write-Host "- Toggle admin/utilizator" -ForegroundColor White
