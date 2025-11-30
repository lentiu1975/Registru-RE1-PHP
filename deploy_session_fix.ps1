$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== DEPLOY SESSION FIX PENTRU CHROME ====" -ForegroundColor Cyan
Write-Host ""

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off
cd /vamactasud.lentiu.ro
put -transfer=binary login.php login.php
cd /vamactasud.lentiu.ro/includes
put -transfer=binary includes/auth.php auth.php
exit
"@

$winscp_script | Out-File -FilePath "temp_session_fix.txt" -Encoding ASCII
& $WINSCP /script=temp_session_fix.txt
Remove-Item temp_session_fix.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host ""
Write-Host "Am actualizat:" -ForegroundColor Cyan
Write-Host "  ✓ login.php - cu setări sesiune pentru Chrome" -ForegroundColor White
Write-Host "  ✓ includes/auth.php - cu aceleași setări sesiune" -ForegroundColor White
Write-Host ""
Write-Host "Modificări făcute:" -ForegroundColor Yellow
Write-Host "  - ini_set('session.cookie_httponly', 1)" -ForegroundColor White
Write-Host "  - ini_set('session.use_only_cookies', 1)" -ForegroundColor White
Write-Host "  - ini_set('session.cookie_samesite', 'Lax')" -ForegroundColor White
Write-Host "  - Căi relative în loc de absolute" -ForegroundColor White
Write-Host ""
Write-Host "IMPORTANT: Șterge complet cache-ul Chrome:" -ForegroundColor Red
Write-Host "  1. Chrome Settings → Privacy → Clear browsing data" -ForegroundColor White
Write-Host "  2. Selectează 'Cookies and other site data'" -ForegroundColor White
Write-Host "  3. Selectează 'Cached images and files'" -ForegroundColor White
Write-Host "  4. Click 'Clear data'" -ForegroundColor White
Write-Host ""
Write-Host "SAU folosește Incognito Mode (Ctrl+Shift+N)" -ForegroundColor Cyan
Write-Host ""
Write-Host "Testează: http://vamactasud.lentiu.ro/login.php" -ForegroundColor Cyan
Write-Host "User: admin" -ForegroundColor White
Write-Host "Pass: admin123" -ForegroundColor White
