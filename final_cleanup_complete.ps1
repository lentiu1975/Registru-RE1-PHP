# STERGERE COMPLETA - exact ca poza ChatGPT

$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"

Write-Host ""
Write-Host "==========================================" -ForegroundColor Red
Write-Host "  CLEANUP FINAL - CA POZA CHATGPT" -ForegroundColor White
Write-Host "==========================================" -ForegroundColor Red
Write-Host ""

$winscp = @"
open ftp://$SERVER_USER`:$SERVER_PASS@$SERVER_IP/
option batch continue
option confirm off

# ROOT - sterge directoare EXTRA
echo Stergere directoare din root...
rmdir /vama.constantasud
rmdir /vama.ctasud
rmdir /vama.lentiu.ro
rmdir /vama
rmdir /home
rmdir /frontend
rmdir /media
rmdir /virtualenv
rmdir /Containere
rmdir /Drapele
rmdir /core
rmdir /deployment_backup
rmdir /.git
rmdir /.claude
rmdir /.local
rmdir /.cache
rmdir /.pip

# ROOT - sterge fisiere EXTRA
echo Stergere fisiere din root...
rm /.env.cpanel
rm /.env.example
rm /deployment_backup.log
rm /deployment_final.log
rm /deployment_rapid.log
rm /deployment_upload.log
rm /deploy_to_server.sh
rm /passenger_wsgi.py~
rm /.bash_history

# PUBLIC_HTML - sterge ABSOLUT TOT
echo Stergere TOTALA public_html...
cd /public_html
rmdir /public_html/vama
rmdir /public_html/vama_app
rmdir /public_html/cgi-bin
rmdir /public_html/.well-known

exit
"@

$winscp | Out-File -FilePath "temp_final_cleanup.txt" -Encoding ASCII
& "C:\Program Files (x86)\WinSCP\WinSCP.com" /script=temp_final_cleanup.txt
Remove-Item temp_final_cleanup.txt

Write-Host ""
Write-Host "==========================================" -ForegroundColor Green
Write-Host "  CLEANUP COMPLET!" -ForegroundColor White
Write-Host "==========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Serverul arata acum ca in poza ChatGPT" -ForegroundColor Yellow
Write-Host "public_html este COMPLET GOL" -ForegroundColor Green
Write-Host ""
