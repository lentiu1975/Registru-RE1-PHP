$w=@"
open ftp://lentiuro:zA5P7lg1l2@185.246.123.91/
option batch continue
option confirm off
cd vamactasud.lentiu.ro/assets/js
put assets\js\search-app.js
exit
"@
$w|Out-File t.txt -Encoding ASCII
& "C:\Program Files (x86)\WinSCP\WinSCP.com" /script=t.txt
rm t.txt
Write-Host ""
Write-Host "FIX UPLOADED!" -F Green
Write-Host "- Nu mai cauta automat" -F Yellow
Write-Host "- Butoane fara caractere speciale" -F Yellow
Write-Host "- Navigare la dreapta" -F Yellow
Write-Host ""
