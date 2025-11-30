$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== DEPLOY TEST ADMIN DIRECT ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off
cd /vamactasud.lentiu.ro
put -transfer=binary test_admin_direct.php test_admin_direct.php
exit
"@

$winscp_script | Out-File -FilePath "temp_test_admin.txt" -Encoding ASCII
& $WINSCP /script=temp_test_admin.txt
Remove-Item temp_test_admin.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host ""
Write-Host "Accesează scriptul de test:" -ForegroundColor Cyan
Write-Host "http://vamactasud.lentiu.ro/test_admin_direct.php" -ForegroundColor White
Write-Host ""
Write-Host "Acest script va testa:" -ForegroundColor Yellow
Write-Host "  - Dacă admin.php există" -ForegroundColor White
Write-Host "  - Link direct către admin.php" -ForegroundColor White
Write-Host "  - JavaScript redirect" -ForegroundColor White
Write-Host "  - Meta refresh fallback" -ForegroundColor White
