# Upload test PHP la root public_html

$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "Upload root_test.php la public_html (ROOT)..." -ForegroundColor Cyan

$winscp = @"
open ftp://$SERVER_USER`:$SERVER_PASS@$SERVER_IP/
option batch continue
option confirm off
cd public_html
put root_test.php
exit
"@

$winscp | Out-File -FilePath "temp_root.txt" -Encoding ASCII
& "C:\Program Files (x86)\WinSCP\WinSCP.com" /script=temp_root.txt
Remove-Item temp_root.txt

Write-Host ""
Write-Host "TESTARE:" -ForegroundColor Yellow
Write-Host ""
Write-Host "1. TEST ROOT:     https://lentiu.ro/root_test.php" -ForegroundColor Cyan
Write-Host "   (PHP in public_html direct)" -ForegroundColor Gray
Write-Host ""
Write-Host "2. TEST SUBFOLDER: https://vama.lentiu.ro/test.php" -ForegroundColor Cyan
Write-Host "   (PHP in public_html/vama)" -ForegroundColor Gray
Write-Host ""
Write-Host "REZULTATE:" -ForegroundColor Yellow
Write-Host "  - Daca AMBELE merg = problema rezolvata!" -ForegroundColor Green
Write-Host "  - Daca ROOT merge, VAMA nu = problema cu subdirectorul" -ForegroundColor Magenta
Write-Host "  - Daca NICIUNUL nu merge = PHP dezactivat total" -ForegroundColor Red
Write-Host ""
