<?php
session_start();
$_SESSION['user_id'] = 1; // Simulează autentificarea

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Structura Tabelelor</h1>";

require_once 'config/database.php';

$conn = getDbConnection();

echo "<h2>1. Coloane din tabela 'manifests':</h2>";
$result = $conn->query("DESCRIBE manifests");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>2. Coloane din tabela 'manifest_entries':</h2>";
$result = $conn->query("DESCRIBE manifest_entries");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>3. Sample data from manifest_entries (1 rând):</h2>";
$result = $conn->query("SELECT * FROM manifest_entries LIMIT 1");
if ($row = $result->fetch_assoc()) {
    echo "<pre>";
    print_r($row);
    echo "</pre>";
}
?>
