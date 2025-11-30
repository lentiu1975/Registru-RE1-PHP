$w=@"
open ftp://lentiuro:zA5P7lg1l2@185.246.123.91/
cd vamactasud.lentiu.ro
mkdir -p assets
cd assets
mkdir -p js
cd js
put assets\js\app.js
exit
"@
$w|Out-File t.txt -Encoding ASCII
& "C:\Program Files (x86)\WinSCP\WinSCP.com" /script=t.txt
rm t.txt
Write-Host ""
Write-Host "========================================" -F Green
Write-Host "  JavaScript app.js uploaded!" -F White
Write-Host "========================================" -F Green
Write-Host ""
Write-Host "Search functionality is now enabled!" -F Green
Write-Host "Test at: https://vamactasud.lentiu.ro/" -F Cyan
Write-Host ""
