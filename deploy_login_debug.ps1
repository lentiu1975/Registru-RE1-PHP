$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== DEPLOY LOGIN DEBUG ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off
cd /vamactasud.lentiu.ro
put -transfer=binary login_debug.php login_debug.php
exit
"@

$winscp_script | Out-File -FilePath "temp_login_debug.txt" -Encoding ASCII
& $WINSCP /script=temp_login_debug.txt
Remove-Item temp_login_debug.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host ""
Write-Host "Accesează scriptul de debug:" -ForegroundColor Cyan
Write-Host "http://vamactasud.lentiu.ro/login_debug.php" -ForegroundColor White
Write-Host ""
Write-Host "Acest script va arăta EXACT ce se întâmplă la login!" -ForegroundColor Yellow
Write-Host "  - Verifică conexiunea la baza de date" -ForegroundColor White
Write-Host "  - Caută utilizatorul" -ForegroundColor White
Write-Host "  - Verifică parola" -ForegroundColor White
Write-Host "  - Setează sesiunea" -ForegroundColor White
Write-Host "  - Arată orice eroare PHP" -ForegroundColor White
