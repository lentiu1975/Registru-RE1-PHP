# Download .htaccess din public_html pentru a vedea ce contine

$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "Download .htaccess din public_html..." -ForegroundColor Cyan

$winscp = @"
open ftp://$SERVER_USER`:$SERVER_PASS@$SERVER_IP/
option batch continue
option confirm off

cd public_html
get .htaccess htaccess_public_html.txt

exit
"@

$winscp | Out-File -FilePath "temp_dl_ht.txt" -Encoding ASCII
& "C:\Program Files (x86)\WinSCP\WinSCP.com" /script=temp_dl_ht.txt
Remove-Item temp_dl_ht.txt

Write-Host ""
if (Test-Path "htaccess_public_html.txt") {
    Write-Host "Continut .htaccess din public_html:" -ForegroundColor Yellow
    Write-Host "======================================" -ForegroundColor Gray
    Get-Content "htaccess_public_html.txt"
    Write-Host "======================================" -ForegroundColor Gray
} else {
    Write-Host ".htaccess NU exista in public_html" -ForegroundColor Green
}
Write-Host ""
