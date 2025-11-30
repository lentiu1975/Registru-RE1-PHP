$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== VERIFICA FISIERE JS PE SERVER ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off
cd /vamactasud.lentiu.ro
lcd "C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP"
put -transfer=binary check_js_files.php
exit
"@

$winscp_script | Out-File -FilePath "temp_check.txt" -Encoding ASCII
& $WINSCP /script=temp_check.txt
Remove-Item temp_check.txt

Write-Host ""
Write-Host "==== ACCESEAZA: ====" -ForegroundColor Green
Write-Host "http://vamactasud.lentiu.ro/check_js_files.php" -ForegroundColor White
