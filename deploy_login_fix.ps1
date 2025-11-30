$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== DEPLOY LOGIN FIX ====" -ForegroundColor Cyan
Write-Host "Uploadează login.php actualizat..." -ForegroundColor Yellow

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off
cd /vamactasud.lentiu.ro
put -transfer=binary login.php login.php
exit
"@

$winscp_script | Out-File -FilePath "temp_login.txt" -Encoding ASCII
& $WINSCP /script=temp_login.txt
Remove-Item temp_login.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host "login.php a fost actualizat - acum redirecționează către admin_new.php!" -ForegroundColor Cyan
Write-Host ""
Write-Host "Încearcă din nou login:" -ForegroundColor Yellow
Write-Host "http://vamactasud.lentiu.ro/login.php" -ForegroundColor White
Write-Host "Username: admin" -ForegroundColor White
Write-Host "Parolă: admin123" -ForegroundColor White
