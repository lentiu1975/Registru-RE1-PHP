# Arata structura serverului

$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"

Write-Host "STRUCTURA CURENTA SERVER:" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Gray

$winscp = @"
open ftp://$SERVER_USER`:$SERVER_PASS@$SERVER_IP/
option batch continue
option confirm off

echo ===== ROOT =====
ls

echo .
echo ===== PUBLIC_HTML =====
cd public_html
ls

exit
"@

$winscp | Out-File -FilePath "temp_show.txt" -Encoding ASCII
& "C:\Program Files (x86)\WinSCP\WinSCP.com" /script=temp_show.txt
Remove-Item temp_show.txt

Write-Host "==========================================" -ForegroundColor Gray
