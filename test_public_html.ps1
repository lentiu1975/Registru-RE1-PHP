# Upload test.php la public_html pentru a testa daca PHP merge acolo

$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host ""
Write-Host "Upload test.php la public_html..." -ForegroundColor Cyan

$winscp = @"
open ftp://$SERVER_USER`:$SERVER_PASS@$SERVER_IP/
option batch continue
option confirm off
option transfer binary

cd public_html
put test.php

exit
"@

$winscp | Out-File -FilePath "temp_test_public.txt" -Encoding ASCII
& "C:\Program Files (x86)\WinSCP\WinSCP.com" /script=temp_test_public.txt
Remove-Item temp_test_public.txt

Write-Host ""
Write-Host "TESTEAZA ACUM:" -ForegroundColor Yellow
Write-Host ""
Write-Host "1. public_html:  https://lentiu.ro/test.php" -ForegroundColor Cyan
Write-Host "2. subdomeniu:   https://vama.lentiu.ro/test.php" -ForegroundColor Cyan
Write-Host ""
Write-Host "Daca 1 merge si 2 nu = problema e la subdomeniu" -ForegroundColor Magenta
Write-Host "Daca NICIUNUL nu merge = PHP dezactivat total" -ForegroundColor Red
Write-Host ""
