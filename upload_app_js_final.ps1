$w=@"
open ftp://lentiuro:zA5P7lg1l2@185.246.123.91/
option batch continue
option confirm off
cd vamactasud.lentiu.ro
mkdir assets
cd assets
mkdir js
cd js
put assets\js\app.js
exit
"@
$w|Out-File t.txt -Encoding ASCII
& "C:\Program Files (x86)\WinSCP\WinSCP.com" /script=t.txt
rm t.txt
Write-Host ""
Write-Host "========================================" -F Green
Write-Host "  JavaScript Deployed!" -F White
Write-Host "========================================" -F Green
Write-Host ""
Write-Host "app.js uploaded to /assets/js/" -F Green
Write-Host ""
Write-Host "Test search now at:" -F Yellow
Write-Host "  https://vamactasud.lentiu.ro/" -F Cyan
Write-Host ""
Write-Host "Try searching for:" -F Yellow
Write-Host "  - Any container number from the database" -F White
Write-Host "  - Manifest numbers" -F White
Write-Host "  - Seal numbers" -F White
Write-Host ""
