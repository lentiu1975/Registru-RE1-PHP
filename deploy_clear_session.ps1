$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== DEPLOY CLEAR SESSION ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off
cd /vamactasud.lentiu.ro
put -transfer=binary clear_session.php clear_session.php
exit
"@

$winscp_script | Out-File -FilePath "temp_clear.txt" -Encoding ASCII
& $WINSCP /script=temp_clear.txt
Remove-Item temp_clear.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host ""
Write-Host "INSTRUCȚIUNI PENTRU CHROME:" -ForegroundColor Yellow
Write-Host ""
Write-Host "1. Accesează: http://vamactasud.lentiu.ro/clear_session.php" -ForegroundColor Cyan
Write-Host "   (Acest script va șterge TOATE sesiunile și cookie-urile vechi)" -ForegroundColor White
Write-Host ""
Write-Host "2. Vei fi redirecționat automat către login.php" -ForegroundColor Cyan
Write-Host ""
Write-Host "3. Loghează-te cu:" -ForegroundColor Cyan
Write-Host "   User: admin" -ForegroundColor White
Write-Host "   Pass: admin123" -ForegroundColor White
Write-Host ""
Write-Host "Acum ar trebui să funcționeze perfect și pe Chrome normal!" -ForegroundColor Green
