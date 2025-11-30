$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== VERIFICARE STRUCTURA SERVER ====" -ForegroundColor Cyan

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off

# Verifica root
pwd
ls

# Verifica public_html
cd public_html
pwd
ls

# Verifica vama
cd vama
pwd
ls

# Verifica api folder
cd api
pwd
ls
cd ..

exit
"@

$winscp_script | Out-File -FilePath "temp_check.txt" -Encoding ASCII
& $WINSCP /script=temp_check.txt
Remove-Item temp_check.txt
