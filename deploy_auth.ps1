$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== DEPLOY AUTH.PHP ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off
cd /vamactasud.lentiu.ro/includes
lcd includes
put -transfer=binary auth.php auth.php
exit
"@

$winscp_script | Out-File -FilePath "temp_auth.txt" -Encoding ASCII
& $WINSCP /script=temp_auth.txt
Remove-Item temp_auth.txt

Write-Host ""
Write-Host "Auth.php uploadat!" -ForegroundColor Green
