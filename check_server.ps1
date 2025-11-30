# Verifica structura serverului

$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "VERIFICARE STRUCTURA SERVER" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Gray
Write-Host ""

$winscp = @"
open ftp://$SERVER_USER`:$SERVER_PASS@$SERVER_IP/
option batch continue
option confirm off

echo ===== ROOT DIRECTOR =====
pwd
ls

echo .
echo ===== PUBLIC_HTML =====
cd public_html
pwd
ls

echo .
echo ===== PUBLIC_HTML/VAMA =====
cd vama
pwd
ls

exit
"@

$winscp | Out-File -FilePath "temp_check.txt" -Encoding ASCII
& "C:\Program Files (x86)\WinSCP\WinSCP.com" /script=temp_check.txt
Remove-Item temp_check.txt

Write-Host ""
Write-Host "==========================================" -ForegroundColor Gray
