$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== UPLOAD IMAGINI SI SEARCH.PHP FIX ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off

# Navigate to site root
cd /vamactasud.lentiu.ro

# Upload search.php fix
cd api
put -transfer=binary api\search.php search.php
cd ..

# Create images folder structure
mkdir images
cd images
mkdir containere
cd containere
mkdir 22G1
mkdir 45G1
cd ../..

# Upload container images 22G1
cd images/containere/22G1
lcd images\containere\22G1
put -transfer=binary *.jpg
put -transfer=binary *.png
put -transfer=binary *.jpeg
lcd ..\..\..
cd ../../..

# Upload container images 45G1
cd images/containere/45G1
lcd images\containere\45G1
put -transfer=binary *.jpg
put -transfer=binary *.png
put -transfer=binary *.jpeg
lcd ..\..\..
cd ../../..

# Upload fallback container image
cd images/containere
lcd images\containere
put -transfer=binary Containere.png
lcd ..\..
cd ../..

exit
"@

$winscp_script | Out-File -FilePath "temp_images_fix.txt" -Encoding ASCII
& $WINSCP /script=temp_images_fix.txt
Remove-Item temp_images_fix.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host "Imaginile containerelor au fost uploadate!" -ForegroundColor Cyan
Write-Host "Test cautare: http://vamactasud.lentiu.ro" -ForegroundColor Yellow
