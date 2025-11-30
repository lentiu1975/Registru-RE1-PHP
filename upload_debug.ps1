# Upload debug.php pentru a vedea exact ce eroare este

$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"

Write-Host ""
Write-Host "==========================================" -ForegroundColor Yellow
Write-Host "  UPLOAD DEBUG SCRIPT" -ForegroundColor White
Write-Host "==========================================" -ForegroundColor Yellow
Write-Host ""

$winscp = @"
open ftp://$SERVER_USER`:$SERVER_PASS@$SERVER_IP/
option batch continue
option confirm off
option transfer binary

cd vamactasud.lentiu.ro

put debug.php

exit
"@

$winscp | Out-File -FilePath "temp_debug.txt" -Encoding ASCII
& "C:\Program Files (x86)\WinSCP\WinSCP.com" /script=temp_debug.txt
Remove-Item temp_debug.txt

Write-Host ""
Write-Host "==========================================" -ForegroundColor Green
Write-Host "  DEBUG UPLOADED!" -ForegroundColor White
Write-Host "==========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Acceseaza pentru a vedea erorile:" -ForegroundColor Yellow
Write-Host "  https://vamactasud.lentiu.ro/debug.php" -ForegroundColor Cyan
Write-Host ""
