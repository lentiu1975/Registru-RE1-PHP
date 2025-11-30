# Upload .htaccess nou pentru PHP 8.4

$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "Upload .htaccess pentru PHP 8.4..." -ForegroundColor Cyan

$winscp = @"
open ftp://$SERVER_USER`:$SERVER_PASS@$SERVER_IP/
option batch continue
option confirm off
option transfer ascii
cd public_html/vama
rm .htaccess
put htaccess_php84.txt
mv htaccess_php84.txt .htaccess
exit
"@

$winscp | Out-File -FilePath "temp_fix.txt" -Encoding ASCII
& $WINSCP /script=temp_fix.txt
Remove-Item temp_fix.txt

Write-Host ""
Write-Host ".htaccess actualizat pentru PHP 8.4!" -ForegroundColor Green
Write-Host ""
Write-Host "Testati: https://vama.lentiu.ro/test.php" -ForegroundColor Cyan
Write-Host ""
