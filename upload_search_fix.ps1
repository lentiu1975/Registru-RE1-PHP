$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off
cd public_html/vama
put api\test.php api/test.php
exit
"@

# Create temp script file
$winscp_script | Out-File -FilePath "temp_upload.txt" -Encoding ASCII

# Execute WinSCP
& $WINSCP /script=temp_upload.txt

# Cleanup
Remove-Item temp_upload.txt

Write-Host ""
Write-Host "Repaired search.php uploaded!" -ForegroundColor Green
Write-Host "- Removed broken ships JOIN" -ForegroundColor Cyan
Write-Host "- Restored to working version" -ForegroundColor Cyan
