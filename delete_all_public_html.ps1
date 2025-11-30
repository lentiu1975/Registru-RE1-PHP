# Sterge ABSOLUT TOT din public_html

$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"

Write-Host ""
Write-Host "==========================================" -ForegroundColor Red
Write-Host "  STERGERE TOTALA public_html" -ForegroundColor White
Write-Host "==========================================" -ForegroundColor Red
Write-Host ""

$winscp = @"
open ftp://$SERVER_USER`:$SERVER_PASS@$SERVER_IP/
option batch continue
option confirm off

cd public_html

# Sterge TOATE fisierele
rm *.*
rm .*

# Sterge TOATE directoarele
rmdir *

exit
"@

$winscp | Out-File -FilePath "temp_del_pub.txt" -Encoding ASCII
& "C:\Program Files (x86)\WinSCP\WinSCP.com" /script=temp_del_pub.txt
Remove-Item temp_del_pub.txt

Write-Host ""
Write-Host "==========================================" -ForegroundColor Green
Write-Host "  public_html COMPLET GOL!" -ForegroundColor White
Write-Host "==========================================" -ForegroundColor Green
Write-Host ""
