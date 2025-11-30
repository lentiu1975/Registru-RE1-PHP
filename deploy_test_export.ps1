$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== DEPLOY TEST EXPORT ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off
cd /vamactasud.lentiu.ro
lcd "C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP"
put -transfer=binary test_export_simple.php
exit
"@

$winscp_script | Out-File -FilePath "temp_test_export.txt" -Encoding ASCII
& $WINSCP /script=temp_test_export.txt
Remove-Item temp_test_export.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host ""
Write-Host "Accesează:" -ForegroundColor Cyan
Write-Host "http://vamactasud.lentiu.ro/test_export_simple.php" -ForegroundColor White
