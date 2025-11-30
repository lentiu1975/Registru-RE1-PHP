$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== DEPLOY TEST SQL COMMANDS ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off
cd /vamactasud.lentiu.ro
put -transfer=binary test_sql_commands.php test_sql_commands.php
exit
"@

$winscp_script | Out-File -FilePath "temp_test_sql.txt" -Encoding ASCII
& $WINSCP /script=temp_test_sql.txt
Remove-Item temp_test_sql.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host ""
Write-Host "Accesează:" -ForegroundColor Cyan
Write-Host "http://vamactasud.lentiu.ro/test_sql_commands.php" -ForegroundColor White
Write-Host ""
Write-Host "Acest script va testa fiecare comandă SQL individual și va arăta exact unde se blochează." -ForegroundColor Yellow
