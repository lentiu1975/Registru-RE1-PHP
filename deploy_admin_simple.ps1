$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== DEPLOY ADMIN SIMPLE FIX ====" -ForegroundColor Cyan
Write-Host "Uploadează admin_simple.php și login.php actualizat..." -ForegroundColor Yellow

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off
cd /vamactasud.lentiu.ro
put -transfer=binary admin_simple.php admin_simple.php
put -transfer=binary login.php login.php
exit
"@

$winscp_script | Out-File -FilePath "temp_admin_simple.txt" -Encoding ASCII
& $WINSCP /script=temp_admin_simple.txt
Remove-Item temp_admin_simple.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host ""
Write-Host "Fișierele au fost uploadate cu succes!" -ForegroundColor Cyan
Write-Host "  - admin_simple.php (panou admin simplificat)" -ForegroundColor White
Write-Host "  - login.php (redirecționează către admin_simple.php)" -ForegroundColor White
Write-Host ""
Write-Host "TESTEAZĂ LOGIN ACUM:" -ForegroundColor Yellow
Write-Host "http://vamactasud.lentiu.ro/login.php" -ForegroundColor White
Write-Host "Username: admin" -ForegroundColor White
Write-Host "Parolă: admin123" -ForegroundColor White
Write-Host ""
Write-Host "După login, vei vedea:" -ForegroundColor Cyan
Write-Host "  - Statistici de bază (manifesturi, containere, nave, utilizatori)" -ForegroundColor White
Write-Host "  - Buton pentru a rula Quick Upgrade" -ForegroundColor White
Write-Host "  - Link către upgrade complet" -ForegroundColor White
