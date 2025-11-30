<?php
/**
 * Finalizează upgrade-ul V2 - verifică ce câmpuri lipsesc înainte de adăugare
 */

// Activează output buffering pentru afișare progresivă
ob_implicit_flush(true);
ob_end_flush();

require_once 'config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><title>Finalizare Upgrade V2</title>";
echo "<style>
    body{font-family:Arial;padding:20px;background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);min-height:100vh;}
    .container{background:white;padding:40px;border-radius:12px;max-width:900px;margin:0 auto;box-shadow:0 20px 60px rgba(0,0,0,0.3);}
    h1{color:#667eea;margin-bottom:20px;}
    .command{background:#2d3748;color:#e2e8f0;padding:12px;border-radius:4px;font-family:monospace;font-size:13px;margin:5px 0;overflow-x:auto;}
    .success{color:green;font-weight:bold;}
    .error{color:red;font-weight:bold;}
    .skip{color:orange;}
    .info{color:#17a2b8;}
    .btn{background:#667eea;color:white;padding:15px 30px;text-decoration:none;border-radius:8px;display:inline-block;margin-top:20px;}
    .btn:hover{background:#764ba2;}
</style>";
echo "</head><body><div class='container'>";
echo "<h1>🔧 Finalizare Upgrade V2 (Smart)</h1>";

$conn = getDbConnection();
$success = 0;
$errors = 0;
$skipped = 0;

// Funcție pentru a verifica dacă un câmp există în tabel
function columnExists($conn, $table, $column) {
    $result = $conn->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
    return ($result && $result->num_rows > 0);
}

// Funcție pentru a verifica dacă un tabel există
function tableExists($conn, $table) {
    $result = $conn->query("SHOW TABLES LIKE '{$table}'");
    return ($result && $result->num_rows > 0);
}

echo "<h2>📋 Pas 1: Verificare Tabele și Câmpuri</h2>";
flush();

// Lista task-urilor
$tasks = [];

// 1. Verifică import_templates
if (!tableExists($conn, 'import_templates')) {
    $tasks[] = [
        'type' => 'create_table',
        'description' => 'CREATE TABLE import_templates',
        'sql' => "CREATE TABLE IF NOT EXISTS `import_templates` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL UNIQUE,
            `description` TEXT,
            `column_mappings` JSON NOT NULL,
            `is_default` TINYINT(1) DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];
} else {
    echo "<p class='info'>✓ Tabela import_templates există deja</p>";
    flush();
}

// 2. Verifică template default
$templateCheck = $conn->query("SELECT COUNT(*) as count FROM import_templates WHERE name = 'Template Standard'");
$templateRow = $templateCheck ? $templateCheck->fetch_assoc() : null;
if (!$templateRow || $templateRow['count'] == 0) {
    $tasks[] = [
        'type' => 'insert_data',
        'description' => 'INSERT template default',
        'sql' => "INSERT IGNORE INTO `import_templates` (`name`, `description`, `column_mappings`, `is_default`)
         VALUES (
             'Template Standard',
             'Template implicit pentru import Excel',
             '{\"manifest_number\":\"A\",\"ship_name\":\"B\",\"arrival_date\":\"C\",\"container_number\":\"D\",\"seal_number\":\"E\",\"goods_description\":\"F\"}',
             1
         )"
    ];
} else {
    echo "<p class='info'>✓ Template Standard există deja</p>";
    flush();
}

// 3. Verifică câmpurile users
$userColumns = [
    'is_admin' => "TINYINT(1) DEFAULT 0 AFTER `email`",
    'is_active' => "TINYINT(1) DEFAULT 1 AFTER `is_admin`",
    'full_name' => "VARCHAR(200) DEFAULT NULL AFTER `username`",
    'company_name' => "VARCHAR(200) DEFAULT NULL AFTER `full_name`",
    'updated_at' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `last_login`"
];

foreach ($userColumns as $column => $definition) {
    if (!columnExists($conn, 'users', $column)) {
        $tasks[] = [
            'type' => 'add_column',
            'description' => "ADD COLUMN users.{$column}",
            'sql' => "ALTER TABLE `users` ADD COLUMN `{$column}` {$definition}"
        ];
    } else {
        echo "<p class='info'>✓ Câmpul users.{$column} există deja</p>";
        flush();
    }
}

// 4. UPDATE admin user
$tasks[] = [
    'type' => 'update_data',
    'description' => 'UPDATE users SET is_admin pentru admin',
    'sql' => "UPDATE `users` SET `is_admin` = 1, `is_active` = 1 WHERE `username` = 'admin'"
];

echo "<hr>";
echo "<h2>⚙️ Pas 2: Execuție Task-uri (" . count($tasks) . " task-uri de executat)</h2>";
flush();

if (count($tasks) == 0) {
    echo "<div style='background:#d4edda;padding:20px;border-radius:8px;border-left:4px solid #28a745;margin:20px 0;'>";
    echo "<h2 style='color:#28a745;margin:0;'>✅ TOTUL ESTE DEJA ACTUALIZAT!</h2>";
    echo "<p style='margin:10px 0 0 0;'>Nu este nevoie de niciun upgrade. Toate tabelele și câmpurile există deja.</p>";
    echo "</div>";
    echo "<a href='admin.php' class='btn' style='background:#28a745;'>🏠 Înapoi la Admin</a>";
} else {
    foreach ($tasks as $index => $task) {
        $num = $index + 1;
        $shortSql = strlen($task['sql']) > 80 ? substr($task['sql'], 0, 77) . "..." : $task['sql'];

        echo "<div class='command'>[{$num}/" . count($tasks) . "] {$task['description']}<br>";
        flush();

        $result = $conn->query($task['sql']);

        if ($result === TRUE) {
            echo "<span class='success'>✓ SUCCES</span>";
            $success++;
        } else {
            $error = $conn->error;
            echo "<span class='error'>✗ EROARE: {$error}</span>";
            $errors++;
        }

        echo "</div>";
        flush();
    }

    echo "<hr>";
    echo "<h2>📊 Rezumat</h2>";
    echo "<p><strong>Succes:</strong> <span class='success'>{$success}</span></p>";
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

        echo "<a href='finish_upgrade_v2.php' class='btn'>🔄 Încearcă Din Nou</a> ";
        echo "<a href='admin.php' class='btn' style='background:#6c757d;'>🏠 Înapoi la Admin</a>";
    }
}

echo "</div></body></html>";
?>
