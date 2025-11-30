$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== DEPLOY TEST DETAILS API ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off
cd /vamactasud.lentiu.ro
lcd "C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP"
put -transfer=binary test_details_api.php
exit
"@

$winscp_script | Out-File -FilePath "temp_test_details.txt" -Encoding ASCII
& $WINSCP /script=temp_test_details.txt
Remove-Item temp_test_details.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host ""
Write-Host "Accesează:" -ForegroundColor Cyan
Write-Host "http://vamactasud.lentiu.ro/test_details_api.php" -ForegroundColor White
Write-Host ""
Write-Host "Va afișa lista de manifeste. Click pe unul pentru a-l testa." -ForegroundColor Yellow
