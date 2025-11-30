$SERVER_IP = "185.246.123.91"
$SERVER_USER = "lentiuro"
$SERVER_PASS = "zA5P7lg1l2"
$WINSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"

Write-Host "==== FIX: Redeploy Admin + Add Cache Buster ====" -ForegroundColor Cyan
Write-Host ""

# Modifică admin_new.php pentru a adăuga cache buster la script-uri
$adminContent = Get-Content "admin_new.php" -Raw
$version = Get-Date -Format "yyyyMMddHHmmss"

# Adaugă versiune la script-uri pentru a forța reload
$adminContent = $adminContent -replace 'assets/js/import-excel.js', "assets/js/import-excel.js?v=$version"
$adminContent = $adminContent -replace 'assets/js/manifest-management.js', "assets/js/manifest-management.js?v=$version"

# Salvează temporar
$adminContent | Out-File -FilePath "admin_new_cache_fixed.php" -Encoding UTF8

Write-Host "✓ Adăugat cache buster: v=$version" -ForegroundColor Green

# Upload la server
$winscp_script = @"
open ftp://${SERVER_USER}:${SERVER_PASS}@${SERVER_IP}/
option batch continue
option confirm off
cd /vamactasud.lentiu.ro
lcd "C:\Users\Laurentiu\Desktop\Proiect RE1 - PHP"
put -transfer=binary admin_new_cache_fixed.php admin_new.php
exit
"@

$winscp_script | Out-File -FilePath "temp_fix_cache.txt" -Encoding ASCII
& $WINSCP /script=temp_fix_cache.txt
Remove-Item temp_fix_cache.txt
Remove-Item admin_new_cache_fixed.php

Write-Host ""
Write-Host "==== DEPLOY FINALIZAT ====" -ForegroundColor Green
Write-Host ""
Write-Host "IMPORTANT:" -ForegroundColor Yellow
Write-Host "1. Deschide http://vamactasud.lentiu.ro/admin_new.php" -ForegroundColor White
Write-Host "2. Apasă CTRL+SHIFT+R (sau CTRL+F5) pentru Hard Refresh" -ForegroundColor White
Write-Host "3. Sau: Deschide Console (F12) și Right-Click pe Refresh → Empty Cache and Hard Reload" -ForegroundColor White
Write-Host ""
