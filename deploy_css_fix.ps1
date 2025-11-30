$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== DEPLOY CSS FIX ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off

lcd C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP
cd /vamactasud.lentiu.ro/assets/css
put -transfer=binary assets\css\search-style.css search-style.css

exit
"@

$winscp_script | Out-File -FilePath "temp_css.txt" -Encoding ASCII
& $WINSCP /script=temp_css.txt
Remove-Item temp_css.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host "CSS actualizat - info bar arată mai bine" -ForegroundColor Cyan
