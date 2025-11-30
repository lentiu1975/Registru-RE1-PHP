<?php
session_start();
$_SESSION['user_id'] = 1;

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

$conn = getDbConnection();

echo "<h1>Adăugare Coloane Lipsă în manifest_entries</h1>";

// Doar coloanele care CHIAR lipsesc
$columns_to_add = [
    'numar_curent' => 'INT DEFAULT 0 COMMENT "Numar curent auto-increment"',
    'numar_manifest' => 'VARCHAR(100) COMMENT "Copie a permit_number"',
    'data_inregistrare' => 'DATE COMMENT "Data inregistrarii"',
    'linie_maritima' => 'VARCHAR(200) COMMENT "Linie maritima"',
    'observatii' => 'TEXT COMMENT "Observatii"',
    'model_container' => 'VARCHAR(100) COMMENT "Primele 4 litere container + tip"'
];

echo "<h2>PASUL 1: Adăugare coloane noi</h2>";

foreach ($columns_to_add as $col_name => $col_def) {
    try {
        $sql = "ALTER TABLE manifest_entries ADD COLUMN $col_name $col_def";
        $conn->query($sql);
        echo "<p style='color: green;'>✅ Adăugat: <strong>$col_name</strong></p>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<p style='color: orange;'>⚠️ Coloana <strong>$col_name</strong> există deja</p>";
        } else {
            echo "<p style='color: red;'>❌ Eroare la $col_name: " . $e->getMessage() . "</p>";
        }
    }
}

echo "<hr>";
echo "<h2>PASUL 2: Populare coloane noi cu date din coloanele existente</h2>";

// Copiază permit_number în numar_manifest
try {
    $conn->query("UPDATE manifest_entries SET numar_manifest = permit_number WHERE numar_manifest IS NULL OR numar_manifest = ''");
    echo "<p style='color: green;'>✅ Copiat permit_number → numar_manifest</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Eroare: " . $e->getMessage() . "</p>";
}

// Generează model_container (primele 4 litere din container_number + container_type)
try {
    $conn->query("UPDATE manifest_entries
                  SET model_container = CONCAT(LEFT(container_number, 4), container_type)
                  WHERE (model_container IS NULL OR model_container = '')
                  AND container_number IS NOT NULL
                  AND container_type IS NOT NULL");
    echo "<p style='color: green;'>✅ Generat model_container = LEFT(container_number, 4) + container_type</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Eroare: " . $e->getMessage() . "</p>";
}

// Generează numar_curent (1, 2, 3, 4...)
try {
    $conn->query("SET @row_number = 0");
    $conn->query("UPDATE manifest_entries SET numar_curent = (@row_number:=@row_number + 1) ORDER BY id");
    echo "<p style='color: green;'>✅ Generat numar_curent (1, 2, 3...)</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Eroare: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>PASUL 3: Verificare rezultate</h2>";

$result = $conn->query("SELECT
    id,
    container_number,
    container_type,
    model_container,
    permit_number,
    numar_manifest,
    numar_curent
FROM manifest_entries
LIMIT 5");

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>container_number</th><th>container_type</th><th>model_container</th><th>permit_number</th><th>numar_manifest</th><th>numar_curent</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['container_number']) . "</td>";
    echo "<td>" . htmlspecialchars($row['container_type']) . "</td>";
    echo "<td><strong>" . htmlspecialchars($row['model_container']) . "</strong></td>";
    echo "<td>" . htmlspecialchars($row['permit_number']) . "</td>";
    echo "<td>" . htmlspecialchars($row['numar_manifest']) . "</td>";
    echo "<td><strong>" . $row['numar_curent'] . "</strong></td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr>";
echo "<h2>✅ TERMINAT!</h2>";
echo "<p>Acum ai toate coloanele necesare în MySQL!</p>";
echo "<p><a href='check_table_structure.php'>→ Verifică structura finală</a></p>";
?>
