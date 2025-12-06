$SERVER_IP = '185.246.123.91'
$SERVER_USER = 'lentiuro'
$SERVER_PASS = 'zA5P7lg1l2'
$WINSCP = 'C:\Program Files (x86)\WinSCP\WinSCP.com'

Write-Host "==== DEPLOY COPY TO CLIPBOARD FEATURE ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off
cd /vamactasud.lentiu.ro/assets/js
lcd "C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP\assets\js"
put -transfer=binary search-app.js
cd /vamactasud.lentiu.ro/assets/css
lcd "C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP\assets\css"
put -transfer=binary search-style.css
exit
"@

$winscp_script | Out-File -FilePath 'temp_deploy.txt' -Encoding ASCII
& $WINSCP /script=temp_deploy.txt
Remove-Item temp_deploy.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host ""
Write-Host "Acum poti da click pe:" -ForegroundColor Yellow
Write-Host "- Pozitie RE1 (ex: 160/160/2/2090 - 03.12.2025)" -ForegroundColor White
Write-Host "- Numar sumara (ex: 25RO01000OXVNDWAT8)" -ForegroundColor White
Write-Host "pentru a copia automat in clipboard!" -ForegroundColor White
