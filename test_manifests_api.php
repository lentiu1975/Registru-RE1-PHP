<?php
session_start();

// Simulăm autentificarea
$_SESSION['user_id'] = 1;

echo "<h1>Test API Manifests</h1>";
echo "<h2>Verificare errori PHP</h2>";

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h3>Include files...</h3>";
require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<h3>Get connection...</h3>";
try {
    $conn = getDbConnection();
    echo "✓ Conexiune OK<br>";
} catch (Exception $e) {
    echo "✗ Eroare conexiune: " . $e->getMessage() . "<br>";
    exit;
}

echo "<h3>Test query manifests...</h3>";
try {
    $query = "SELECT
        m.id,
        m.manifest_number,
        m.ship_name,
        m.arrival_date,
        COUNT(me.id) as container_count,
        m.created_at
    FROM manifests m
    LEFT JOIN manifest_entries me ON m.manifest_number = me.manifest_number
    GROUP BY m.id
    ORDER BY m.arrival_date DESC
    LIMIT 10";

    $result = $conn->query($query);

    if ($result) {
        echo "✓ Query executat cu succes<br>";
        echo "Rânduri găsite: " . $result->num_rows . "<br><br>";

        while ($row = $result->fetch_assoc()) {
            echo "Manifest: " . $row['manifest_number'] . " - " . $row['ship_name'] . "<br>";
        }
    } else {
        echo "✗ Eroare query: " . $conn->error . "<br>";
    }
} catch (Exception $e) {
    echo "✗ Excepție: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h3>Test API direct</h3>";
echo '<a href="api/manifests/list.php?page=1" target="_blank">Test API Manifests List</a>';
?>
