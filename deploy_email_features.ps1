$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== DEPLOY EMAIL FEATURES ====" -ForegroundColor Cyan
Write-Host "1. Setari Email (admin)" -ForegroundColor White
Write-Host "2. Generare parola" -ForegroundColor White
Write-Host "3. Trimitere credentiale email" -ForegroundColor White
Write-Host "4. Email obligatoriu la creare utilizator" -ForegroundColor White
Write-Host ""

$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off

cd /vamactasud.lentiu.ro
lcd "C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP"
put -transfer=binary admin_new.php

cd /vamactasud.lentiu.ro/api
lcd "C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP\api"
put -transfer=binary email_settings.php
put -transfer=binary send_credentials.php
put -transfer=binary users.php

exit
"@

$winscp_script | Out-File -FilePath "temp_email.txt" -Encoding ASCII
& $WINSCP /script=temp_email.txt
Remove-Item temp_email.txt

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host ""
Write-Host "IMPORTANT: Ruleaza acest SQL in phpMyAdmin:" -ForegroundColor Yellow
Write-Host ""
Write-Host "CREATE TABLE IF NOT EXISTS email_settings (" -ForegroundColor White
Write-Host "    id INT PRIMARY KEY DEFAULT 1," -ForegroundColor White
Write-Host "    smtp_host VARCHAR(255) NOT NULL DEFAULT ''," -ForegroundColor White
Write-Host "    smtp_port INT NOT NULL DEFAULT 465," -ForegroundColor White
Write-Host "    smtp_username VARCHAR(255) NOT NULL DEFAULT ''," -ForegroundColor White
Write-Host "    smtp_password VARCHAR(255) NOT NULL DEFAULT ''," -ForegroundColor White
Write-Host "    smtp_encryption ENUM('ssl', 'tls', 'none') DEFAULT 'ssl'," -ForegroundColor White
Write-Host "    from_email VARCHAR(255) NOT NULL DEFAULT ''," -ForegroundColor White
Write-Host "    from_name VARCHAR(255) DEFAULT 'Registru RE1'," -ForegroundColor White
Write-Host "    is_active TINYINT(1) DEFAULT 1," -ForegroundColor White
Write-Host "    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP," -ForegroundColor White
Write-Host "    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP" -ForegroundColor White
Write-Host ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;" -ForegroundColor White
Write-Host ""
Write-Host "Testeaza: http://vamactasud.lentiu.ro/admin_new.php" -ForegroundColor Yellow
Write-Host "CTRL+F5 pentru refresh complet!" -ForegroundColor Yellow
