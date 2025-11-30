<?php
session_start();
$_SESSION['user_id'] = 1; // Simulează autentificarea

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test API Details</h1>";

// Testează cu un manifest_number real
$testManifestNumber = '2024/RO/001'; // Înlocuiește cu un număr real din baza ta

echo "<h2>1. Test Direct Query</h2>";

require_once 'config/database.php';
require_once 'includes/functions.php';

$conn = getDbConnection();

echo "<h3>Selectează un manifest_number din baza de date:</h3>";
$result = $conn->query("SELECT DISTINCT manifest_number FROM manifests LIMIT 5");
echo "<ul>";
while ($row = $result->fetch_assoc()) {
    $mn = $row['manifest_number'];
    echo "<li><a href='?test=$mn'>$mn</a></li>";
}
echo "</ul>";

if (isset($_GET['test'])) {
    $manifestNumber = $_GET['test'];

    echo "<hr><h2>Testing manifest: $manifestNumber</h2>";

    echo "<h3>Query pentru manifest info:</h3>";
    $query = "SELECT
        m.manifest_number,
        m.arrival_date,
        MAX(m.created_at) as created_at,
        MAX(me.ship_name) as ship_name
     FROM manifests m
     LEFT JOIN manifest_entries me ON m.manifest_number = me.permit_number
     WHERE m.manifest_number = ?
     GROUP BY m.manifest_number";

    echo "<pre>" . htmlspecialchars($query) . "</pre>";

    try {
        $stmt = $conn->prepare($query);
        $stmt->bind_param('s', $manifestNumber);
        $stmt->execute();
        $result = $stmt->get_result();
        $manifestInfo = $result->fetch_assoc();

        if ($manifestInfo) {
            echo "<p style='color: green;'><strong>✓ Manifest găsit!</strong></p>";
            echo "<pre>" . print_r($manifestInfo, true) . "</pre>";
        } else {
            echo "<p style='color: red;'><strong>✗ Manifest NU a fost găsit!</strong></p>";
        }

    } catch (Exception $e) {
        echo "<p style='color: red;'><strong>✗ Eroare: " . $e->getMessage() . "</strong></p>";
    }

    echo "<h3>Query pentru containere:</h3>";
    $query2 = "SELECT
        id,
        container_number,
        seal_number,
        goods_description,
        created_at
    FROM manifest_entries
    WHERE permit_number = ?
    ORDER BY container_number ASC";

    echo "<pre>" . htmlspecialchars($query2) . "</pre>";

    try {
        $stmt2 = $conn->prepare($query2);
        $stmt2->bind_param('s', $manifestNumber);
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        $containers = [];
        while ($row = $result2->fetch_assoc()) {
            $containers[] = $row;
        }

        echo "<p><strong>Containere găsite: " . count($containers) . "</strong></p>";

        if (count($containers) > 0) {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Container</th><th>Sigiliu</th><th>Descriere</th></tr>";
            foreach ($containers as $c) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($c['container_number']) . "</td>";
                echo "<td>" . htmlspecialchars($c['seal_number'] ?? '-') . "</td>";
                echo "<td>" . htmlspecialchars($c['goods_description'] ?? '-') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }

    } catch (Exception $e) {
        echo "<p style='color: red;'><strong>✗ Eroare: " . $e->getMessage() . "</strong></p>";
    }

    echo "<hr><h2>Test API Call</h2>";
    echo "<p><a href='api/manifests/details.php?manifest_number=" . urlencode($manifestNumber) . "' target='_blank'>Deschide API în tab nou</a></p>";

    // Simulează apelul API
    ob_start();
    $_GET['manifest_number'] = $manifestNumber;
    include 'api/manifests/details.php';
    $apiOutput = ob_get_clean();

    echo "<h3>Output API:</h3>";
    echo "<pre>" . htmlspecialchars($apiOutput) . "</pre>";

    // Decodează JSON
    $decoded = json_decode($apiOutput, true);
    if ($decoded) {
        echo "<h3>JSON Decodat:</h3>";
        echo "<pre>" . print_r($decoded, true) . "</pre>";
    } else {
        echo "<p style='color: red;'><strong>✗ JSON invalid! Eroare: " . json_last_error_msg() . "</strong></p>";
    }
}
?>

<style>
body { font-family: Arial, sans-serif; padding: 20px; }
pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; }
table { border-collapse: collapse; margin: 10px 0; }
th { background: #4472C4; color: white; padding: 8px; }
td { padding: 5px; border: 1px solid #ddd; }
</style>
