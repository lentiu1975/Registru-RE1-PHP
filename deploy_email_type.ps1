$SERVER_IP = '185.246.123.91'
$SERVER_USER = 'lentiuro'
$SERVER_PASS = 'zA5P7lg1l2'
$WINSCP = 'C:\Program Files (x86)\WinSCP\WinSCP.com'

Write-Host "==== DEPLOY EMAIL TYPE FIX ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off
cd /vamactasud.lentiu.ro
lcd "C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP"
put -transfer=binary admin_new.php
cd /vamactasud.lentiu.ro/api
lcd "C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP\api"
put -transfer=binary send_credentials.php
exit
"@

$winscp_script | Out-File -FilePath 'temp_deploy.txt' -Encoding ASCII
& $WINSCP /script=temp_deploy.txt
Remove-Item temp_deploy.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host "Acum email-ul va avea mesaj diferit:" -ForegroundColor Yellow
Write-Host "- Cont nou: 'Contul dumneavoastra a fost creat'" -ForegroundColor White
Write-Host "- Parola modificata: 'Parola contului a fost modificata'" -ForegroundColor White
