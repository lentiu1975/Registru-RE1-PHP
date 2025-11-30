# STERGERE ABSOLUT TOTALA - TOT de pe server

$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host ""
Write-Host "==========================================" -ForegroundColor Red
Write-Host "  STERGERE TOTALA - ABSOLUT TOT" -ForegroundColor White
Write-Host "==========================================" -ForegroundColor Red
Write-Host ""
Write-Host "ATENTIE: Acest script va sterge:" -ForegroundColor Yellow
Write-Host "  - /vama.lentiu.ro (tot)" -ForegroundColor Red
Write-Host "  - /public_html/vama (tot)" -ForegroundColor Red
Write-Host "  - /public_html/vama_app (tot)" -ForegroundColor Red
Write-Host "  - /vama (tot)" -ForegroundColor Red
Write-Host "  - /home (tot)" -ForegroundColor Red
Write-Host "  - /frontend (tot)" -ForegroundColor Red
Write-Host "  - /media (tot)" -ForegroundColor Red
Write-Host "  - /virtualenv (tot)" -ForegroundColor Red
Write-Host "  - Toate fisierele Python/Django din root" -ForegroundColor Red
Write-Host ""

$winscp_delete = @"
open ftp://$SERVER_USER`:$SERVER_PASS@$SERVER_IP/
option batch continue
option confirm off

# Root - sterge fisiere Python/Django
rm *.py
rm *.pyc
rm .env
rm .gitignore
rm .htaccess
rm *.txt
rm *.md
rm *.bat
rm *.ps1
rm *.webm
rm *.png
rm *.jpg
rm *.jpeg
rm db.sqlite3
rm cookies.txt
rm cookies2.txt

# Sterge directoare principale
rmdir vama.lentiu.ro
rmdir vama
rmdir home
rmdir frontend
rmdir media
rmdir virtualenv
rmdir core
rmdir Containere
rmdir Drapele

# public_html/vama
cd public_html/vama
rm *.*
cd ..
rmdir vama

# public_html/vama_app
cd vama_app
rm *.*
cd ..
rmdir vama_app

exit
"@

$winscp_delete | Out-File -FilePath "temp_delete_all.txt" -Encoding ASCII
& "C:\Program Files (x86)\WinSCP\WinSCP.com" /script=temp_delete_all.txt
Remove-Item temp_delete_all.txt

Write-Host ""
Write-Host "==========================================" -ForegroundColor Green
Write-Host "  STERGERE COMPLETA!" -ForegroundColor White
Write-Host "==========================================" -ForegroundColor Green
Write-Host ""
