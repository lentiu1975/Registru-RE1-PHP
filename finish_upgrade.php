<?php
/**
 * Finalizează upgrade-ul - creează doar tabelele lipsă
 */

// Activează output buffering pentru afișare progresivă
ob_implicit_flush(true);
ob_end_flush();

require_once 'config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><title>Finalizare Upgrade</title>";
echo "<style>
    body{font-family:Arial;padding:20px;background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);min-height:100vh;}
    .container{background:white;padding:40px;border-radius:12px;max-width:900px;margin:0 auto;box-shadow:0 20px 60px rgba(0,0,0,0.3);}
    h1{color:#667eea;margin-bottom:20px;}
    .command{background:#2d3748;color:#e2e8f0;padding:12px;border-radius:4px;font-family:monospace;font-size:13px;margin:5px 0;overflow-x:auto;}
    .success{color:green;font-weight:bold;}
    .error{color:red;font-weight:bold;}
    .skip{color:orange;}
    .btn{background:#667eea;color:white;padding:15px 30px;text-decoration:none;border-radius:8px;display:inline-block;margin-top:20px;}
    .btn:hover{background:#764ba2;}
</style>";
echo "</head><body><div class='container'>";
echo "<h1>🔧 Finalizare Upgrade</h1>";

$conn = getDbConnection();
$success = 0;
$errors = 0;
$skipped = 0;

// SQL-urile pentru tabelele lipsă
$sql_commands = [
    // 1. import_templates
    "CREATE TABLE IF NOT EXISTS `import_templates` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL UNIQUE,
        `description` TEXT,
        `column_mappings` JSON NOT NULL,
        `is_default` TINYINT(1) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // 2. Template default
    "INSERT IGNORE INTO `import_templates` (`name`, `description`, `column_mappings`, `is_default`)
     VALUES (
         'Template Standard',
         'Template implicit pentru import Excel',
         '{\"manifest_number\":\"A\",\"ship_name\":\"B\",\"arrival_date\":\"C\",\"container_number\":\"D\",\"seal_number\":\"E\",\"goods_description\":\"F\"}',
         1
     )",

    // 3. Câmpuri users (fără IF NOT EXISTS - vom gestiona eroarea manual)
    "ALTER TABLE `users` ADD COLUMN `is_admin` TINYINT(1) DEFAULT 0 AFTER `email`",
    "ALTER TABLE `users` ADD COLUMN `is_active` TINYINT(1) DEFAULT 1 AFTER `is_admin`",
    "ALTER TABLE `users` ADD COLUMN `full_name` VARCHAR(200) DEFAULT NULL AFTER `username`",
    "ALTER TABLE `users` ADD COLUMN `company_name` VARCHAR(200) DEFAULT NULL AFTER `full_name`",
    "ALTER TABLE `users` ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `last_login`",

    // 4. Setează admin ca administrator
    "UPDATE `users` SET `is_admin` = 1, `is_active` = 1 WHERE `username` = 'admin'",
];

echo "<p>Se procesează " . count($sql_commands) . " comenzi SQL...</p>";
ob_flush();
flush();

foreach ($sql_commands as $index => $sql) {
    $num = $index + 1;
    $shortSql = strlen($sql) > 80 ? substr($sql, 0, 77) . "..." : $sql;

    echo "<div class='command'>[{$num}/" . count($sql_commands) . "] ";
    ob_flush();
    flush();

    // Execută fără try-catch pentru a permite verificarea erorilor
    $result = $conn->query($sql);

    if ($result === TRUE) {
        echo "<span class='success'>✓</span> {$shortSql}";
        $success++;
    } else {
        $error = $conn->error;

        // Verifică dacă e eroare de "duplicate column" sau "already exists"
        if (stripos($error, 'Duplicate column') !== false ||
            stripos($error, 'Duplicate') !== false ||
            stripos($error, 'already exists') !== false ||
            stripos($error, 'check that column') !== false) {
            echo "<span class='skip'>⚠ SKIP:</span> {$shortSql} <em>(deja există)</em>";
            $skipped++;
            $success++; // Considerăm ca succes dacă deja există
        } else {
            echo "<span class='error'>✗ EROARE:</span> {$shortSql}<br>";
            echo "<span class='error'>Detalii: {$error}</span>";
            $errors++;
        }
    }

    echo "</div>";
    ob_flush();
    flush();
}

echo "<hr>";
echo "<h2>📊 Rezumat</h2>";
echo "<p><strong>Succes:</strong> <span class='success'>{$success}</span></p>";
echo "<p><strong>Omise (deja există):</strong> <span class='skip'>{$skipped}</span></p>";
echo "<p><strong>Erori:</strong> <span class='error'>{$errors}</span></p>";

if ($errors == 0) {
    echo "<div style='background:#d4edda;padding:20px;border-radius:8px;border-left:4px solid #28a745;margin:20px 0;'>";
    echo "<h2 style='color:#28a745;margin:0;'>✅ UPGRADE FINALIZAT CU SUCCES!</h2>";
    echo "<p style='margin:10px 0 0 0;'>Toate tabelele și câmpurile au fost create.</p>";
    echo "</div>";

    echo "<a href='check_upgrade_status.php' class='btn'>🔍 Verifică Status</a> ";
    echo "<a href='admin.php' class='btn' style='background:#28a745;'>🏠 Înapoi la Admin</a>";
} else {
    echo "<div style='background:#fff3cd;padding:20px;border-radius:8px;border-left:4px solid #ffc107;margin:20px 0;'>";
    echo "<h2 style='color:#856404;margin:0;'>⚠ UPGRADE PARȚIAL</h2>";
    echo "<p style='margin:10px 0 0 0;'>Au apărut {$errors} erori. Verifică detaliile de mai sus.</p>";
    echo "</div>";

    echo "<a href='finish_upgrade.php' class='btn'>🔄 Încearcă Din Nou</a> ";
    echo "<a href='admin.php' class='btn' style='background:#6c757d;'>🏠 Înapoi la Admin</a>";
}

echo "</div></body></html>";
?>
