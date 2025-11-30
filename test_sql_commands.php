<?php
/**
 * Test individual SQL commands to see which one blocks
 */

// Activează afișare imediată
ob_implicit_flush(true);
ob_end_flush();

require_once 'config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><title>Test SQL Commands</title>";
echo "<style>
    body{font-family:Arial;padding:20px;background:#f5f5f5;}
    .test{background:white;padding:15px;margin:10px 0;border-radius:5px;border-left:4px solid #667eea;}
    .success{color:green;font-weight:bold;}
    .error{color:red;font-weight:bold;}
    .info{color:blue;}
</style>";
echo "</head><body>";
echo "<h1>Test SQL Commands Individual</h1>";

$conn = getDbConnection();

// Test 1: Verifică dacă tabela users există
echo "<div class='test'>";
echo "<h3>Test 1: Verifică tabela users</h3>";
flush();

$result = $conn->query("SHOW TABLES LIKE 'users'");
if ($result && $result->num_rows > 0) {
    echo "<p class='success'>✓ Tabela users există</p>";
} else {
    echo "<p class='error'>✗ Tabela users NU există!</p>";
}
echo "</div>";
flush();

// Test 2: Arată structura curentă a tabelei users
echo "<div class='test'>";
echo "<h3>Test 2: Structura tabelei users</h3>";
flush();

$result = $conn->query("DESCRIBE users");
if ($result) {
    echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td><strong>{$row['Field']}</strong></td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='error'>✗ Eroare: " . $conn->error . "</p>";
}
echo "</div>";
flush();

// Test 3: Încearcă să adauge câmpul is_admin
echo "<div class='test'>";
echo "<h3>Test 3: Adaugă câmpul is_admin</h3>";
echo "<p class='info'>Executând: ALTER TABLE users ADD COLUMN is_admin TINYINT(1) DEFAULT 0 AFTER email</p>";
flush();

$sql = "ALTER TABLE `users` ADD COLUMN `is_admin` TINYINT(1) DEFAULT 0 AFTER `email`";
$start_time = microtime(true);

echo "<p>Începe execuția SQL...</p>";
flush();

$result = $conn->query($sql);
$end_time = microtime(true);
$duration = round($end_time - $start_time, 2);

echo "<p>Execuție finalizată în {$duration} secunde</p>";
flush();

if ($result === TRUE) {
    echo "<p class='success'>✓ SUCCES! Câmpul is_admin a fost adăugat.</p>";
} else {
    $error = $conn->error;
    if (stripos($error, 'Duplicate column') !== false) {
        echo "<p class='info'>⚠ Câmpul deja există (normal dacă rulezi a doua oară)</p>";
    } else {
        echo "<p class='error'>✗ EROARE: {$error}</p>";
    }
}
echo "</div>";
flush();

// Test 4: Verifică din nou structura
echo "<div class='test'>";
echo "<h3>Test 4: Structura finală a tabelei users</h3>";
flush();

$result = $conn->query("DESCRIBE users");
if ($result) {
    echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $highlight = ($row['Field'] == 'is_admin') ? ' style="background:#d4edda;"' : '';
        echo "<tr{$highlight}>";
        echo "<td><strong>{$row['Field']}</strong></td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='error'>✗ Eroare: " . $conn->error . "</p>";
}
echo "</div>";
flush();

// Test 5: Verifică conexiunea la baza de date
echo "<div class='test'>";
echo "<h3>Test 5: Info conexiune MySQL</h3>";
echo "<p><strong>Server:</strong> " . $conn->host_info . "</p>";
echo "<p><strong>Protocol:</strong> " . $conn->protocol_version . "</p>";
echo "<p><strong>Client:</strong> " . $conn->client_info . "</p>";
echo "</div>";
flush();

echo "<hr>";
echo "<p><a href='finish_upgrade.php' style='background:#667eea;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block;'>Înapoi la Finish Upgrade</a></p>";
echo "</body></html>";
?>
