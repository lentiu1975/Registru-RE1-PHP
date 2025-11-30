# Upload htaccess.txt si redenumeste in .htaccess

$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "Upload .htaccess..." -ForegroundColor Cyan

$winscp = @"
open ftp://$SERVER_USER`:$SERVER_PASS@$SERVER_IP/
option batch continue
option confirm off
option transfer ascii
cd public_html/vama
put htaccess.txt
mv htaccess.txt .htaccess
exit
"@

$winscp | Out-File -FilePath "temp_ht.txt" -Encoding ASCII
& $WINSCP /script=temp_ht.txt
Remove-Item temp_ht.txt

Write-Host ""
Write-Host ".htaccess uplodat!" -ForegroundColor Green
Write-Host ""
Write-Host "Testati: https://vama.lentiu.ro" -ForegroundColor Cyan
Write-Host ""
