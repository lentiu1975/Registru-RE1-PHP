# Sterge .htaccess din public_html/vama

$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "Stergere .htaccess din public_html/vama..." -ForegroundColor Yellow

$winscp = @"
open ftp://$SERVER_USER`:$SERVER_PASS@$SERVER_IP/
option batch continue
option confirm off

cd public_html/vama
rm .htaccess

exit
"@

$winscp | Out-File -FilePath "temp_del.txt" -Encoding ASCII
& "C:\Program Files (x86)\WinSCP\WinSCP.com" /script=temp_del.txt
Remove-Item temp_del.txt

Write-Host ""
Write-Host ".htaccess STERS din public_html/vama!" -ForegroundColor Green
Write-Host ""
Write-Host "Testeaza ACUM: https://vama.lentiu.ro/test.php" -ForegroundColor Cyan
Write-Host ""
