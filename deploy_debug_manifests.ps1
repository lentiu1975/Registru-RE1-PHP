$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== DEPLOY DEBUG MANIFESTS ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off
cd /vamactasud.lentiu.ro
put -transfer=binary debug_manifests.php debug_manifests.php
exit
"@

$winscp_script | Out-File -FilePath "temp_debug.txt" -Encoding ASCII
& $WINSCP /script=temp_debug.txt
Remove-Item temp_debug.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host ""
Write-Host "Accesează scriptul de debug:" -ForegroundColor Cyan
Write-Host "http://vamactasud.lentiu.ro/debug_manifests.php" -ForegroundColor White
Write-Host ""
Write-Host "Vei vedea:" -ForegroundColor Yellow
Write-Host "  - Total rânduri în tabel" -ForegroundColor White
Write-Host "  - Manifesturi UNICE" -ForegroundColor White
Write-Host "  - Distribuție pe ani" -ForegroundColor White
Write-Host "  - Ultimele 10 manifesturi" -ForegroundColor White
Write-Host "  - Manifesturi duplicate" -ForegroundColor White
