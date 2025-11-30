$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== DEPLOY ADMIN_NEW.PHP ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off
cd /vamactasud.lentiu.ro
put -transfer=binary admin_new.php admin_new.php
exit
"@

$winscp_script | Out-File -FilePath "temp_finish.txt" -Encoding ASCII
& $WINSCP /script=temp_finish.txt
Remove-Item temp_finish.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host ""
Write-Host "Rulează scriptul de finalizare:" -ForegroundColor Cyan
Write-Host "http://vamactasud.lentiu.ro/admin_new.php" -ForegroundColor White
Write-Host ""
Write-Host "Acest script va:" -ForegroundColor Yellow
Write-Host "  - Crea tabela import_templates (lipsă)" -ForegroundColor White
Write-Host "  - Adăuga câmpuri lipsă în users" -ForegroundColor White
Write-Host "  - Seta utilizatorul admin ca administrator" -ForegroundColor White
