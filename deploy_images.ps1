$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== DEPLOY SHIP & FLAG IMAGES ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off

lcd C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP

cd /vamactasud.lentiu.ro/images
mkdir steaguri

cd nave
put -transfer=binary images\nave\*.jpg
put -transfer=binary images\nave\*.png

cd ../steaguri
put -transfer=binary images\steaguri\*.png

exit
"@

$winscp_script | Out-File -FilePath "temp_images.txt" -Encoding ASCII
& $WINSCP /script=temp_images.txt
Remove-Item temp_images.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host "Poze nave si steaguri uploadate pe server" -ForegroundColor Cyan
