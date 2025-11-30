# Upload check_users.php pentru debug

$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"

Write-Host ""
Write-Host "Uploading check_users.php..." -ForegroundColor Yellow

$winscp = @"
open ftp://$SERVER_USER`:$SERVER_PASS@$SERVER_IP/
option batch continue
option confirm off
option transfer binary

cd vamactasud.lentiu.ro

put check_users.php

exit
"@

$winscp | Out-File -FilePath "temp_check.txt" -Encoding ASCII
& "C:\Program Files (x86)\WinSCP\WinSCP.com" /script=temp_check.txt
Remove-Item temp_check.txt

Write-Host ""
Write-Host "Acceseaza:" -ForegroundColor Yellow
Write-Host "  https://vamactasud.lentiu.ro/check_users.php" -ForegroundColor Cyan
Write-Host ""
