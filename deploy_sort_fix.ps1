$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== DEPLOY SORT FIX ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off

cd /vamactasud.lentiu.ro/api
put -transfer=binary api\search.php search.php

exit
"@

$winscp_script | Out-File -FilePath "temp_sort.txt" -Encoding ASCII
& $WINSCP /script=temp_sort.txt
Remove-Item temp_sort.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host "Sortare corectata: ORDER BY position_number ASC" -ForegroundColor Cyan
Write-Host "Rezultatele vor fi afisate in ordine: 56, 57, 58, etc." -ForegroundColor Green
