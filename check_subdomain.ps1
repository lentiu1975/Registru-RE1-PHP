# Verifica directorul subdomeniului

$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "VERIFICARE SUBDOMAIN vama.lentiu.ro" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Gray
Write-Host ""

$winscp = @"
open ftp://$SERVER_USER`:$SERVER_PASS@$SERVER_IP/
option batch continue
option confirm off

echo ===== DIRECTOR SUBDOMENIU =====
cd vama.lentiu.ro
pwd
ls

exit
"@

$winscp | Out-File -FilePath "temp_sub.txt" -Encoding ASCII
& "C:\Program Files (x86)\WinSCP\WinSCP.com" /script=temp_sub.txt
Remove-Item temp_sub.txt

Write-Host ""
Write-Host "==========================================" -ForegroundColor Gray
Write-Host "CONCLUZIE:" -ForegroundColor Yellow
Write-Host "Subdomenul vama.lentiu.ro pointeaza la:" -ForegroundColor White
Write-Host "/home/lentiuro/vama.lentiu.ro" -ForegroundColor Cyan
Write-Host ""
Write-Host "NU la /public_html/vama!" -ForegroundColor Red
Write-Host ""
