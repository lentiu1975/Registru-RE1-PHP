$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== DEPLOY TEST API ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off
cd /vamactasud.lentiu.ro
put -transfer=binary test_manifests_api.php
put -transfer=binary check_manifests_structure.php
put -transfer=binary test_manifest_list_direct.php
exit
"@

$winscp_script | Out-File -FilePath "temp_test_api.txt" -Encoding ASCII
& $WINSCP /script=temp_test_api.txt
Remove-Item temp_test_api.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host ""
Write-Host "Accesează:" -ForegroundColor Cyan
Write-Host "http://vamactasud.lentiu.ro/test_manifests_api.php" -ForegroundColor White
