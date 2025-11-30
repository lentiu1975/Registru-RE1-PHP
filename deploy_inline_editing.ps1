$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== DEPLOY INLINE EDITING ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off

cd /vamactasud.lentiu.ro/assets/js
lcd "C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP\assets\js"
put -transfer=binary manifest-management.js

exit
"@

$winscp_script | Out-File -FilePath "temp_edit.txt" -Encoding ASCII
& $WINSCP /script=temp_edit.txt
Remove-Item temp_edit.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host ""
Write-Host "Teste:" -ForegroundColor Yellow
Write-Host "1. http://vamactasud.lentiu.ro/admin_new.php" -ForegroundColor White
Write-Host "2. CTRL+F5" -ForegroundColor White
Write-Host "3. Tab Manifeste -> Click 👁️" -ForegroundColor White
Write-Host "4. Dublu-click pe Linie Maritima sau Observatii" -ForegroundColor White
Write-Host ""
Write-Host "FUNCTIONALITATE:" -ForegroundColor Cyan
Write-Host "- Dublu-click pe celula pentru editare" -ForegroundColor White
Write-Host "- Enter sau click afara pentru salvare" -ForegroundColor White
Write-Host "- ESC pentru anulare" -ForegroundColor White
Write-Host "- Celula devine verde 2 secunde dupa salvare cu succes" -ForegroundColor White
