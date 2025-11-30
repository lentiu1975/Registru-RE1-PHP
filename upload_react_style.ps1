$w=@"
open ftp://lentiuro:zA5P7lg1l2@185.246.123.91/
option batch continue
option confirm off
cd vamactasud.lentiu.ro

mkdir assets
cd assets
mkdir js
mkdir css
cd js
put assets\js\app.js
cd ..
cd css
put assets\css\style.css

exit
"@
$w|Out-File t.txt -Encoding ASCII
& "C:\Program Files (x86)\WinSCP\WinSCP.com" /script=t.txt
rm t.txt
Write-Host ""
Write-Host "==========================================" -F Green
Write-Host "  React-Style Layout Deployed!" -F White
Write-Host "==========================================" -F Green
Write-Host ""
Write-Host "Updated files:" -F Yellow
Write-Host "  - /assets/js/app.js (new layout)" -F Cyan
Write-Host "  - /assets/css/style.css (React styles)" -F Cyan
Write-Host ""
Write-Host "Test now at:" -F Yellow
Write-Host "  https://vamactasud.lentiu.ro/" -F Cyan
Write-Host ""
Write-Host "Features:" -F Yellow
Write-Host "  - Two column layout (Details | Images)" -F White
Write-Host "  - Navigation between results" -F White
Write-Host "  - Styled like React version" -F White
Write-Host ""
