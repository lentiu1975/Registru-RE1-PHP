$winscp = @"
open ftp://lentiuro:zA5P7lg1l2@185.246.123.91/
option batch continue
option confirm off
cd vamactasud.lentiu.ro
put check_simple.php
exit
"@

$winscp | Out-File temp_simple.txt -Encoding ASCII
& "C:\Program Files (x86)\WinSCP\WinSCP.com" /script=temp_simple.txt
Remove-Item temp_simple.txt

Write-Host "Acceseaza: https://vamactasud.lentiu.ro/check_simple.php" -ForegroundColor Cyan
