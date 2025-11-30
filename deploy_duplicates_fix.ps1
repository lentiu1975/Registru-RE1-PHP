$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== DEPLOY DUPLICATES FIX ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off

cd /vamactasud.lentiu.ro/api/manifests
lcd "C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP\api\manifests"
put -transfer=binary details.php

cd /vamactasud.lentiu.ro/assets/js
lcd "C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP\assets\js"
put -transfer=binary manifest-management.js

exit
"@

$winscp_script | Out-File -FilePath "temp_dup.txt" -Encoding ASCII
& $WINSCP /script=temp_dup.txt
Remove-Item temp_dup.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host ""
Write-Host "Testeaza:" -ForegroundColor Yellow
Write-Host "1. http://vamactasud.lentiu.ro/admin_new.php" -ForegroundColor White
Write-Host "2. CTRL+F5" -ForegroundColor White
Write-Host "3. Tab Manifeste -> Click 👁️" -ForegroundColor White
Write-Host ""
Write-Host "Containerele duplicate (CAIU4517061) vor fi colorate cu GALBEN!" -ForegroundColor Cyan
