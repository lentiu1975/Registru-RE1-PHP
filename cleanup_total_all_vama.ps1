# STERGERE TOTALA - TOATE directoarele legate de vama

$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host ""
Write-Host "==========================================" -ForegroundColor Red
Write-Host "  STERGERE TOTALA - TOATE FISIERELE VAMA" -ForegroundColor White
Write-Host "==========================================" -ForegroundColor Red
Write-Host ""

# 1. Sterge COMPLET /vama.lentiu.ro
Write-Host "1. Stergere /vama.lentiu.ro..." -ForegroundColor Yellow

$winscp1 = @"
open ftp://$SERVER_USER`:$SERVER_PASS@$SERVER_IP/
option batch continue
option confirm off

cd vama.lentiu.ro
rm *.*
rmdir *

exit
"@

$winscp1 | Out-File -FilePath "temp_clean1.txt" -Encoding ASCII
& "C:\Program Files (x86)\WinSCP\WinSCP.com" /script=temp_clean1.txt
Remove-Item temp_clean1.txt

# 2. Sterge COMPLET /public_html/vama
Write-Host "2. Stergere /public_html/vama..." -ForegroundColor Yellow

$winscp2 = @"
open ftp://$SERVER_USER`:$SERVER_PASS@$SERVER_IP/
option batch continue
option confirm off

cd public_html/vama
rm *.*
rmdir *

exit
"@

$winscp2 | Out-File -FilePath "temp_clean2.txt" -Encoding ASCII
& "C:\Program Files (x86)\WinSCP\WinSCP.com" /script=temp_clean2.txt
Remove-Item temp_clean2.txt

# 3. Sterge /public_html/vama_app (daca exista)
Write-Host "3. Stergere /public_html/vama_app..." -ForegroundColor Yellow

$winscp3 = @"
open ftp://$SERVER_USER`:$SERVER_PASS@$SERVER_IP/
option batch continue
option confirm off

cd public_html/vama_app
rm *.*
rmdir *

exit
"@

$winscp3 | Out-File -FilePath "temp_clean3.txt" -Encoding ASCII
& "C:\Program Files (x86)\WinSCP\WinSCP.com" /script=temp_clean3.txt
Remove-Item temp_clean3.txt

Write-Host ""
Write-Host "==========================================" -ForegroundColor Green
Write-Host "  TOATE FISIERELE STERSE!" -ForegroundColor White
Write-Host "==========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Directoare curatate:" -ForegroundColor Yellow
Write-Host "  - /vama.lentiu.ro" -ForegroundColor White
Write-Host "  - /public_html/vama" -ForegroundColor White
Write-Host "  - /public_html/vama_app" -ForegroundColor White
Write-Host ""
