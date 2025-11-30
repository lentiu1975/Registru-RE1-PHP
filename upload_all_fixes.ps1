$w=@"
open ftp://lentiuro:zA5P7lg1l2@185.246.123.91/
option batch continue
option confirm off
cd vamactasud.lentiu.ro

cd api
put api\search.php

cd ..
cd assets/js
put assets\js\search-app.js

exit
"@
$w|Out-File t.txt -Encoding ASCII
& "C:\Program Files (x86)\WinSCP\WinSCP.com" /script=t.txt
rm t.txt
Write-Host ""
Write-Host "==========================================" -F Green
Write-Host "  TOATE PROBLEMELE REZOLVATE!" -F White
Write-Host "==========================================" -F Green
Write-Host ""
Write-Host "Fixat:" -F Yellow
Write-Host "  - Colete (nu Sigiliu)" -F Green
Write-Host "  - Tip operatiune" -F Green
Write-Host "  - Numar sumara (multi-line)" -F Green
Write-Host "  - Imagine container (algoritm)" -F Green
Write-Host "  - Sectiune nava (nume + steag + imagine)" -F Green
Write-Host ""
