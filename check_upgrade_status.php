<?php
/**
 * Verifică statusul upgrade-ului - ce tabele există deja
 */

require_once 'config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><title>Status Upgrade</title>";
echo "<style>
    body{font-family:Arial;padding:20px;background:#f5f5f5;}
    .status{background:white;padding:20px;border-radius:8px;max-width:800px;margin:0 auto;}
    table{width:100%;border-collapse:collapse;margin:20px 0;}
    th,td{border:1px solid #ddd;padding:12px;text-align:left;}
    th{background:#667eea;color:white;}
    .exists{color:green;font-weight:bold;}
    .missing{color:red;}
    h1{color:#667eea;}
    .summary{background:#f0f0f0;padding:15px;border-radius:5px;margin:20px 0;}
</style>";
echo "</head><body><div class='status'>";
echo "<h1>📊 Status Upgrade Bază de Date</h1>";

// Lista tabelelor care ar trebui să existe după upgrade
$requiredTables = [
    'users' => 'Utilizatori (existent)',
    'manifests' => 'Manifesturi (existent)',
    'manifest_entries' => 'Intrări Manifest (existent)',
    'ships' => 'Nave (existent)',
    'database_years' => 'Ani Bază de Date (NOU)',
    'pavilions' => 'Pavilioane (NOU)',
    'container_types' => 'Tipuri Containere (NOU)',
    'import_templates' => 'Template-uri Import (NOU)',
    'import_logs' => 'Log-uri Import (NOU)'
];

// Verifică ce tabele există
$conn = getDbConnection();
$existingTables = [];
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_array()) {
    $existingTables[] = $row[0];
}

echo "<div class='summary'>";
echo "<strong>Total tabele în baza de date:</strong> " . count($existingTables) . "<br>";
echo "<strong>Tabele necesare:</strong> " . count($requiredTables);
echo "</div>";

echo "<table>";
echo "<tr><th>Tabel</th><th>Descriere</th><th>Status</th><th>Număr Înregistrări</th></tr>";

$allExists = true;
foreach ($requiredTables as $table => $description) {
    $exists = in_array($table, $existingTables);
    $status = $exists ? "<span class='exists'>✓ EXISTĂ</span>" : "<span class='missing'>✗ LIPSEȘTE</span>";

    $count = '-';
    if ($exists) {
        $countResult = $conn->query("SELECT COUNT(*) as count FROM `{$table}`");
        if ($countResult) {
            $count = $countResult->fetch_assoc()['count'];
        }
    } else {
        $allExists = false;
    }

    echo "<tr>";
    echo "<td><strong>{$table}</strong></td>";
    echo "<td>{$description}</td>";
    echo "<td>{$status}</td>";
    echo "<td>{$count}</td>";
    echo "</tr>";
}

echo "</table>";

// Verifică câmpuri noi în users
echo "<h2>🔍 Verificare Câmpuri Noi în Tabela 'users'</h2>";
$userFields = ['is_admin', 'is_active', 'full_name', 'company_name', 'updated_at'];
$result = $conn->query("DESCRIBE users");
$existingFields = [];
while ($row = $result->fetch_assoc()) {
    $existingFields[] = $row['Field'];
}

echo "<table>";
echo "<tr><th>Câmp</th><th>Status</th></tr>";
foreach ($userFields as $field) {
    $exists = in_array($field, $existingFields);
    $status = $exists ? "<span class='exists'>✓ EXISTĂ</span>" : "<span class='missing'>✗ LIPSEȘTE</span>";
    echo "<tr><td><strong>{$field}</strong></td><td>{$status}</td></tr>";
}
echo "</table>";

// Rezumat final
echo "<div class='summary'>";
if ($allExists) {
    echo "<h2 style='color:green;'>✅ UPGRADE COMPLET!</h2>";
    echo "<p>Toate tabelele necesare au fost create cu succes.</p>";
    echo "<p><a href='admin.php' style='background:#667eea;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block;margin-top:10px;'>Înapoi la Admin Panel</a></p>";
} else {
    echo "<h2 style='color:orange;'>⚠ UPGRADE INCOMPLET</h2>";
    echo "<p>Unele tabele lipsesc. Upgrade-ul nu s-a finalizat complet.</p>";
    echo "<p><a href='install_upgrade.php' style='background:#667eea;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block;margin-top:10px;'>Reîncearcă Upgrade</a></p>";
}
echo "</div>";

echo "</div></body></html>";
?>
