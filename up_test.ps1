$w=@"
open ftp://lentiuro:zA5P7lg1l2@185.246.123.91/
cd vamactasud.lentiu.ro
put test_search.php
exit
"@
$w|Out-File t.txt -Encoding ASCII
& "C:\Program Files (x86)\WinSCP\WinSCP.com" /script=t.txt
rm t.txt
Write-Host "Test: https://vamactasud.lentiu.ro/test_search.php" -F Cyan
