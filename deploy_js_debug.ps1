$SERVER_IP = '185.246.123.91'
$SERVER_USER = 'lentiuro'
$SERVER_PASS = 'zA5P7lg1l2'
$WINSCP = 'C:\Program Files (x86)\WinSCP\WinSCP.com'

Write-Host "==== DEPLOY MANIFEST-MANAGEMENT.JS ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off
cd /vamactasud.lentiu.ro/assets/js
lcd "C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP\assets\js"
put -transfer=binary manifest-management.js
exit
"@

$winscp_script | Out-File -FilePath 'temp_deploy_js.txt' -Encoding ASCII
& $WINSCP /script=temp_deploy_js.txt
Remove-Item temp_deploy_js.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host "Acum deschide pagina admin, apasa F12 pentru Console, si incearca cautarea." -ForegroundColor Yellow
