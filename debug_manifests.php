<?php
/**
 * DEBUG - Verifică numărul real de manifesturi
 */

require_once 'config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><title>Debug Manifesturi</title>";
echo "<style>body{font-family:monospace;padding:20px;} table{border-collapse:collapse;} th,td{border:1px solid #ccc;padding:8px;text-align:left;}</style>";
echo "</head><body>";
echo "<h1>DEBUG Manifesturi</h1>";

// 1. Total manifesturi
$total = dbFetchOne("SELECT COUNT(*) as count FROM manifests")['count'];
echo "<h2>1. Total rânduri în tabel manifests: <span style='color:red;font-size:2em;'>{$total}</span></h2>";

// 2. Manifesturi unice după număr manifest
$unique = dbFetchOne("SELECT COUNT(DISTINCT manifest_number) as count FROM manifests")['count'];
echo "<h2>2. Manifesturi UNICE (după manifest_number): <span style='color:green;font-size:2em;'>{$unique}</span></h2>";

// 3. Manifesturi pe ani
echo "<h2>3. Distribuție pe ani:</h2>";
$byYear = dbFetchAll("SELECT YEAR(arrival_date) as year, COUNT(*) as count FROM manifests WHERE arrival_date IS NOT NULL GROUP BY YEAR(arrival_date) ORDER BY year DESC");
echo "<table>";
echo "<tr><th>An</th><th>Număr Manifesturi</th></tr>";
foreach ($byYear as $row) {
    echo "<tr><td>{$row['year']}</td><td>{$row['count']}</td></tr>";
}
echo "</table>";

// 4. Ultimele 10 manifesturi
echo "<h2>4. Ultimele 10 manifesturi:</h2>";
$recent = dbFetchAll("SELECT id, manifest_number, ship_name, arrival_date FROM manifests ORDER BY id DESC LIMIT 10");
echo "<table>";
echo "<tr><th>ID</th><th>Număr Manifest</th><th>Navă</th><th>Data Sosire</th></tr>";
foreach ($recent as $row) {
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['manifest_number']}</td>";
    echo "<td>{$row['ship_name']}</td>";
    echo "<td>{$row['arrival_date']}</td>";
    echo "</tr>";
}
echo "</table>";

// 5. Verifică duplicate
echo "<h2>5. Manifesturi Duplicate (același manifest_number):</h2>";
$duplicates = dbFetchAll("SELECT manifest_number, COUNT(*) as count FROM manifests GROUP BY manifest_number HAVING count > 1 ORDER BY count DESC LIMIT 10");
if (count($duplicates) > 0) {
    echo "<table>";
    echo "<tr><th>Număr Manifest</th><th>Apariții</th></tr>";
    foreach ($duplicates as $row) {
        echo "<tr><td>{$row['manifest_number']}</td><td style='color:red;font-weight:bold;'>{$row['count']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:green;'>Nu există duplicate!</p>";
}

// 6. Sugestie
echo "<hr>";
echo "<h2>📊 Recomandare:</h2>";
echo "<p>Dacă vrei să afișezi <strong>manifesturi unice</strong>, folosește:</p>";
echo "<pre>SELECT COUNT(DISTINCT manifest_number) as count FROM manifests</pre>";
echo "<p>Sau manifesturi pentru anul curent:</p>";
echo "<pre>SELECT COUNT(*) as count FROM manifests WHERE YEAR(arrival_date) = YEAR(CURDATE())</pre>";

echo "</body></html>";
?>
