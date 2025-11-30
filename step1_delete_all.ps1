# PASUL 1: Șterge TOT din vama

$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "========================================" -ForegroundColor Red
Write-Host " ȘTERGERE COMPLETĂ FOLDER VAMA" -ForegroundColor Red
Write-Host "========================================" -ForegroundColor Red
Write-Host ""

$winscp_script = @"
open ftp://$SERVER_USER`:$SERVER_PASS@$SERVER_IP/
option batch continue
option confirm off

cd public_html
rm -r vama
mkdir vama

exit
"@

$winscp_script | Out-File -FilePath "temp_delete.txt" -Encoding ASCII

Write-Host "Ștergere în curs..." -ForegroundColor Yellow
& $WINSCP /script=temp_delete.txt

Remove-Item temp_delete.txt

Write-Host ""
Write-Host "✓ TOT ȘTERS! Folder vama este acum GOL" -ForegroundColor Green
Write-Host ""
