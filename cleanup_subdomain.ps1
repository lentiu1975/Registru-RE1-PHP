# Sterge ABSOLUT TOT din vama.lentiu.ro (locatia CORECTA a subdomeniului)

$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host ""
Write-Host "STERGERE COMPLETA vama.lentiu.ro" -ForegroundColor Red
Write-Host "=====================================" -ForegroundColor Gray

$winscp = @"
open ftp://$SERVER_USER`:$SERVER_PASS@$SERVER_IP/
option batch continue
option confirm off

cd vama.lentiu.ro

# Sterge TOT (exceptand .well-known si cgi-bin care sunt sistem)
rm *.*

exit
"@

$winscp | Out-File -FilePath "temp_clean.txt" -Encoding ASCII
& "C:\Program Files (x86)\WinSCP\WinSCP.com" /script=temp_clean.txt
Remove-Item temp_clean.txt

Write-Host ""
Write-Host "Directorul vama.lentiu.ro a fost curatat!" -ForegroundColor Green
Write-Host ""
