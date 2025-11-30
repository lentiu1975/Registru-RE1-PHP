# Upload fișier de test phpinfo.php

$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "Upload phpinfo.php pentru testare..." -ForegroundColor Yellow

$winscp_script = @"
open ftp://$SERVER_USER`:$SERVER_PASS@$SERVER_IP/
option batch continue
option confirm off

cd public_html/vama
put -delete phpinfo.php

exit
"@

$winscp_script | Out-File -FilePath "temp_test.txt" -Encoding ASCII
& $WINSCP /script=temp_test.txt
Remove-Item temp_test.txt

Write-Host ""
Write-Host "Testați: https://vama.lentiu.ro/phpinfo.php" -ForegroundColor Cyan
Write-Host ""
Write-Host "Dacă vedeți informații PHP = PHP funcționează!" -ForegroundColor Green
Write-Host "Dacă se descarcă fișierul = PHP NU este configurat corect" -ForegroundColor Red
Write-Host ""
Write-Host "IMPORTANT: Ștergeți phpinfo.php după test!" -ForegroundColor Yellow
