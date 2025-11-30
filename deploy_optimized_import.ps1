$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== DEPLOY IMPORT OPTIMIZAT ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off

cd /vamactasud.lentiu.ro
put -transfer=binary import_data_optimized.php

exit
"@

$winscp_script | Out-File -FilePath "temp_opt.txt" -Encoding ASCII
& $WINSCP /script=temp_opt.txt
Remove-Item temp_opt.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host ""
Write-Host "Acum acceseaza:" -ForegroundColor Yellow
Write-Host "  https://vamactasud.lentiu.ro/import_data_optimized.php" -ForegroundColor Cyan
Write-Host ""
Write-Host "Scriptul optimizat proceseaza datele linie cu linie si afiseaza progres!" -ForegroundColor Green
