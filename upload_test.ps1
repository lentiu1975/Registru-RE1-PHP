# Upload test.php simplu

$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "Upload test.php..." -ForegroundColor Cyan

$winscp = @"
open ftp://$SERVER_USER`:$SERVER_PASS@$SERVER_IP/
option batch continue
option confirm off
cd public_html/vama
put test.php
exit
"@

$winscp | Out-File -FilePath "temp_test.txt" -Encoding ASCII
& $WINSCP /script=temp_test.txt
Remove-Item temp_test.txt

Write-Host ""
Write-Host "Testati: https://vama.lentiu.ro/test.php" -ForegroundColor Cyan
Write-Host ""
Write-Host "DACA:" -ForegroundColor Yellow
Write-Host "  - Vedeti 'PHP FUNCTIONEAZA!' = PHP merge!" -ForegroundColor Green
Write-Host "  - Se descarca fisierul = PHP NU este activat in cPanel" -ForegroundColor Red
Write-Host ""
