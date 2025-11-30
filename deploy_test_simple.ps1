$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off
cd /public_html/vama
put -transfer=binary test_simple.php
exit
"@

$winscp_script | Out-File -FilePath "temp_simple.txt" -Encoding ASCII
& $WINSCP /script=temp_simple.txt
Remove-Item temp_simple.txt

Write-Host ""
Write-Host "Test uploaded!" -ForegroundColor Green
Write-Host "Access: http://vamactasud.lentiu.ro/test_simple.php" -ForegroundColor Cyan
