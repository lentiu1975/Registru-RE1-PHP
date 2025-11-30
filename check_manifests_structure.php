<?php
session_start();
$_SESSION['user_id'] = 1;

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<h1>Verificare Structură Tabele</h1>";

$conn = getDbConnection();

// Verifică structura tabelei manifests
echo "<h2>Tabela: manifests</h2>";
$result = $conn->query("DESCRIBE manifests");
if ($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Eroare: " . $conn->error;
}

echo "<hr>";

// Verifică structura tabelei manifest_entries
echo "<h2>Tabela: manifest_entries</h2>";
$result = $conn->query("DESCRIBE manifest_entries");
if ($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Eroare: " . $conn->error;
}

echo "<hr>";

// Arată câteva date exemplu
echo "<h2>Date Exemplu</h2>";
$result = $conn->query("SELECT * FROM manifests LIMIT 3");
if ($result && $result->num_rows > 0) {
    echo "<h3>Manifests:</h3><pre>";
    while ($row = $result->fetch_assoc()) {
        print_r($row);
    }
    echo "</pre>";
} else {
    echo "<p>Nu există date în manifests sau eroare: " . $conn->error . "</p>";
}

$result = $conn->query("SELECT * FROM manifest_entries LIMIT 3");
if ($result && $result->num_rows > 0) {
    echo "<h3>Manifest Entries:</h3><pre>";
    while ($row = $result->fetch_assoc()) {
        print_r($row);
    }
    echo "</pre>";
} else {
    echo "<p>Nu există date în manifest_entries</p>";
}
?>
