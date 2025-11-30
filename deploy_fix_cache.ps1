$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== FIX CACHE: Deploy admin_new.php cu versiune ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off
cd /vamactasud.lentiu.ro
lcd "C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP"
put -transfer=binary admin_new.php
exit
"@

$winscp_script | Out-File -FilePath "temp_cache_fix.txt" -Encoding ASCII
& $WINSCP /script=temp_cache_fix.txt
Remove-Item temp_cache_fix.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host ""
Write-Host "PASUL FINAL:" -ForegroundColor Yellow
Write-Host "Deschide pagina si apasa CTRL+SHIFT+R pentru Hard Refresh!" -ForegroundColor White
Write-Host "http://vamactasud.lentiu.ro/admin_new.php" -ForegroundColor Cyan
