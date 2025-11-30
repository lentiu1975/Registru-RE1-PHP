# Curata serverul la structura standard cPanel

$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"

Write-Host ""
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "  CLEANUP LA STRUCTURA STANDARD CPANEL" -ForegroundColor White
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""

$winscp = @"
open ftp://$SERVER_USER`:$SERVER_PASS@$SERVER_IP/
option batch continue
option confirm off

# Sterge directoare EXTRA din root
rmdir vama
rmdir home
rmdir frontend
rmdir media
rmdir virtualenv
rmdir Containere
rmdir Drapele
rmdir core
rmdir deployment_backup
rmdir .git
rmdir .claude
rmdir .pip
rmdir .cache
rmdir .local

# Sterge fisiere EXTRA din root
rm .env
rm .env.cpanel
rm .env.example
rm *.py
rm *.sh
rm *.log
rm *.md
rm *.bat
rm *.ps1
rm *.webm
rm *.png
rm *.jpg

# Curata public_html - sterge vama_app
cd public_html
rmdir vama_app
rm root_test.php
rm test.php

exit
"@

$winscp | Out-File -FilePath "temp_cleanup.txt" -Encoding ASCII
& "C:\Program Files (x86)\WinSCP\WinSCP.com" /script=temp_cleanup.txt
Remove-Item temp_cleanup.txt

Write-Host ""
Write-Host "==========================================" -ForegroundColor Green
Write-Host "  CLEANUP COMPLET!" -ForegroundColor White
Write-Host "==========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Serverul are acum structura standard cPanel" -ForegroundColor Yellow
Write-Host "Gata pentru deploy PHP in public_html/vama" -ForegroundColor Green
Write-Host ""
