# Upload .htaccess pentru a forța executarea PHP

$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "=====================================" -ForegroundColor Cyan
Write-Host " UPLOAD .htaccess - FIX PHP" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""

$winscp_script = @"
open ftp://$SERVER_USER`:$SERVER_PASS@$SERVER_IP/
option batch continue
option confirm off
option transfer ascii

cd vama.lentiu.ro
put .htaccess

exit
"@

$winscp_script | Out-File -FilePath "temp_htaccess.txt" -Encoding ASCII

Write-Host "Upload .htaccess în curs..." -ForegroundColor Yellow
& "C:\Program Files (x86)\WinSCP\WinSCP.com" /script=temp_htaccess.txt

Remove-Item temp_htaccess.txt

Write-Host ""
Write-Host "=====================================" -ForegroundColor Green
Write-Host " .htaccess UPLODAT!" -ForegroundColor Green
Write-Host "=====================================" -ForegroundColor Green
Write-Host ""
Write-Host "Testați ACUM: https://vama.lentiu.ro" -ForegroundColor Cyan
Write-Host ""
Write-Host "Dacă tot se descarcă fișierele:" -ForegroundColor Yellow
Write-Host "1. Accesați cPanel → Select PHP Version" -ForegroundColor White
Write-Host "2. Verificați că PHP este activat pentru domeniu" -ForegroundColor White
Write-Host "3. Selectați PHP 7.4 sau 8.0" -ForegroundColor White
Write-Host ""
