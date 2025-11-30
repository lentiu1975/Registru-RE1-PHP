# Upload config updated care nu va crapa daca nu exista database

$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"

Write-Host ""
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "  UPLOAD CONFIG FIXED" -ForegroundColor White
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""

$winscp = @"
open ftp://$SERVER_USER`:$SERVER_PASS@$SERVER_IP/
option batch continue
option confirm off
option transfer binary

cd vamactasud.lentiu.ro/config

put config\database.php

exit
"@

$winscp | Out-File -FilePath "temp_config_fix.txt" -Encoding ASCII
& "C:\Program Files (x86)\WinSCP\WinSCP.com" /script=temp_config_fix.txt
Remove-Item temp_config_fix.txt

Write-Host ""
Write-Host "==========================================" -ForegroundColor Green
Write-Host "  CONFIG UPDATED!" -ForegroundColor White
Write-Host "==========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Acum index.php ar trebui sa mearga fara eroare 500!" -ForegroundColor Yellow
Write-Host "Testeaza: https://vamactasud.lentiu.ro/" -ForegroundColor Cyan
Write-Host ""
