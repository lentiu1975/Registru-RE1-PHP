$w=@"
open ftp://lentiuro:zA5P7lg1l2@185.246.123.91/
option batch continue
option confirm off
cd vamactasud.lentiu.ro

put index.php

cd assets
cd css
put assets\css\search-style.css

cd ..
cd js
put assets\js\search-app.js

exit
"@
$w|Out-File t.txt -Encoding ASCII
& "C:\Program Files (x86)\WinSCP\WinSCP.com" /script=t.txt
rm t.txt
Write-Host ""
Write-Host "==========================================" -F Green
Write-Host "  EXACT REACT DESIGN DEPLOYED!" -F White
Write-Host "==========================================" -F Green
Write-Host ""
Write-Host "Updated files:" -F Yellow
Write-Host "  - index.php" -F Cyan
Write-Host "  - /assets/css/search-style.css" -F Cyan
Write-Host "  - /assets/js/search-app.js" -F Cyan
Write-Host ""
Write-Host "Design matches screenshot:" -F Yellow
Write-Host "  - Purple gradient background" -F White
Write-Host "  - Two column layout" -F White
Write-Host "  - Navigation buttons" -F White
Write-Host "  - Container + Ship images" -F White
Write-Host ""
Write-Host "Test now:" -F Yellow
Write-Host "  https://vamactasud.lentiu.ro/" -F Cyan
Write-Host ""
