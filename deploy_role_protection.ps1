$SERVER_IP = '185.246.123.91'
$SERVER_USER = 'lentiuro'
$SERVER_PASS = 'zA5P7lg1l2'
$WINSCP = 'C:\Program Files (x86)\WinSCP\WinSCP.com'

Write-Host "==== DEPLOY ROLE PROTECTION ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off
cd /vamactasud.lentiu.ro
lcd "C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP"
put -transfer=binary admin_new.php
exit
"@

$winscp_script | Out-File -FilePath 'temp_deploy.txt' -Encoding ASCII
& $WINSCP /script=temp_deploy.txt
Remove-Item temp_deploy.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host "Acum nu poti modifica propriul rol cand editezi contul tau." -ForegroundColor Yellow
