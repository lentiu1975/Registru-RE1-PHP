# Verifica si sterge .htaccess din vama.lentiu.ro

$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "Verificare .htaccess..." -ForegroundColor Cyan

$winscp = @"
open ftp://$SERVER_USER`:$SERVER_PASS@$SERVER_IP/
option batch continue
option confirm off

cd vama.lentiu.ro
ls -la .htaccess

exit
"@

$winscp | Out-File -FilePath "temp_check_ht.txt" -Encoding ASCII
& "C:\Program Files (x86)\WinSCP\WinSCP.com" /script=temp_check_ht.txt
Remove-Item temp_check_ht.txt

Write-Host ""
Write-Host "Sterg .htaccess din vama.lentiu.ro..." -ForegroundColor Yellow

$winscp2 = @"
open ftp://$SERVER_USER`:$SERVER_PASS@$SERVER_IP/
option batch continue
option confirm off

cd vama.lentiu.ro
rm .htaccess

exit
"@

$winscp2 | Out-File -FilePath "temp_del_ht.txt" -Encoding ASCII
& "C:\Program Files (x86)\WinSCP\WinSCP.com" /script=temp_del_ht.txt
Remove-Item temp_del_ht.txt

Write-Host ""
Write-Host ".htaccess STERS!" -ForegroundColor Green
Write-Host "Testeaza ACUM: https://vama.lentiu.ro/test.php" -ForegroundColor Cyan
Write-Host ""
