$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off
cd /vamactasud.lentiu.ro
put -transfer=binary test_login.php test_login.php
exit
"@

$winscp_script | Out-File -FilePath "temp_test.txt" -Encoding ASCII
& $WINSCP /script=temp_test.txt
Remove-Item temp_test.txt

Write-Host "Test login uploaded!" -ForegroundColor Green
Write-Host "Accesează: http://vamactasud.lentiu.ro/test_login.php" -ForegroundColor Cyan
