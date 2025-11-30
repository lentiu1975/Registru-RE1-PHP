<?php
session_start();
$_SESSION['user_id'] = 1; // Simulează autentificarea

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test Direct API Manifests List</h1>";

require_once 'config/database.php';
require_once 'includes/functions.php';

$conn = getDbConnection();

echo "<h2>Test Query Simplu</h2>";

$query = "SELECT
    m.manifest_number,
    m.arrival_date,
    MAX(me.ship_name) as ship_name,
    COUNT(DISTINCT me.id) as container_count,
    MAX(m.created_at) as created_at
FROM manifests m
LEFT JOIN manifest_entries me ON m.manifest_number = me.permit_number
GROUP BY m.manifest_number
ORDER BY m.arrival_date DESC, MAX(m.created_at) DESC
LIMIT 10";

echo "<pre>Query:\n" . $query . "</pre>";

try {
    $result = $conn->query($query);

    if ($result) {
        echo "<p><strong>✓ Query executat cu succes</strong></p>";
        echo "<p>Rânduri: " . $result->num_rows . "</p>";

        if ($result->num_rows > 0) {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Manifest</th><th>Navă</th><th>Data</th><th>Containere</th><th>Created At</th></tr>";

            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['manifest_number']) . "</td>";
                echo "<td>" . htmlspecialchars($row['ship_name'] ?? '-') . "</td>";
                echo "<td>" . htmlspecialchars($row['arrival_date']) . "</td>";
                echo "<td>" . htmlspecialchars($row['container_count']) . "</td>";
                echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
                echo "</tr>";
            }

            echo "</table>";
        } else {
            echo "<p class='warning'>⚠ Nu există date</p>";
        }
    } else {
        echo "<p class='error'>✗ Eroare: " . $conn->error . "</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Excepție: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>Test API Direct (JSON)</h2>";
echo '<p><a href="api/manifests/list.php?page=1" target="_blank">Deschide API în tab nou</a></p>';

// Simulează apelul API
ob_start();
$_GET['page'] = 1;
include 'api/manifests/list.php';
$apiOutput = ob_get_clean();

echo "<h3>Output API:</h3>";
echo "<pre>" . htmlspecialchars($apiOutput) . "</pre>";

// Încearcă să decodeze JSON
$decoded = json_decode($apiOutput, true);
if ($decoded) {
    echo "<h3>JSON Decodat:</h3>";
    echo "<pre>" . print_r($decoded, true) . "</pre>";
} else {
    echo "<p class='error'>✗ JSON invalid! Eroare: " . json_last_error_msg() . "</p>";
}
?>

<style>
.error { color: red; font-weight: bold; }
.warning { color: orange; font-weight: bold; }
table { border-collapse: collapse; }
th { background: #4472C4; color: white; padding: 8px; }
td { padding: 5px; }
</style>
