<?php
session_start();
$_SESSION['user_id'] = 1;

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

$conn = getDbConnection();

echo "<h1>Verificare și Adăugare Coloane Lipsă</h1>";

// Coloanele care TREBUIE să existe în manifest_entries
$required_columns = [
    'numar_curent' => 'INT DEFAULT 0',
    'numar_manifest' => 'VARCHAR(100)',
    'numar_permis' => 'VARCHAR(100)',
    'numar_pozitie' => 'VARCHAR(50)',
    'cerere_operatiune' => 'VARCHAR(100)',
    'data_inregistrare' => 'DATE',
    'container' => 'VARCHAR(50)',
    'numar_colete' => 'INT',
    'greutate_bruta' => 'DECIMAL(12,2)',
    'descriere_marfa' => 'TEXT',
    'tip_operatiune' => 'VARCHAR(1)',
    'nume_nava' => 'VARCHAR(200)',
    'pavilion_nava' => 'VARCHAR(100)',
    'numar_sumara' => 'VARCHAR(100)',
    'tip_container' => 'VARCHAR(50)',
    'linie_maritima' => 'VARCHAR(200)',
    'observatii' => 'TEXT',
    'model_container' => 'VARCHAR(100)'
];

// Obține coloanele existente
$result = $conn->query("DESCRIBE manifest_entries");
$existing_columns = [];
while ($row = $result->fetch_assoc()) {
    $existing_columns[] = $row['Field'];
}

echo "<h2>Coloane Existente:</h2>";
echo "<pre>";
print_r($existing_columns);
echo "</pre>";

echo "<h2>Coloane care lipsesc și trebuie adăugate:</h2>";
$missing_columns = [];
foreach ($required_columns as $col_name => $col_type) {
    if (!in_array($col_name, $existing_columns)) {
        $missing_columns[$col_name] = $col_type;
        echo "<p style='color: orange;'>❌ Lipsește: <strong>$col_name</strong> ($col_type)</p>";
    } else {
        echo "<p style='color: green;'>✅ Există: <strong>$col_name</strong></p>";
    }
}

if (count($missing_columns) > 0) {
    echo "<hr>";
    echo "<h2>SQL pentru adăugare coloane:</h2>";
    echo "<pre>";
    foreach ($missing_columns as $col_name => $col_type) {
        $sql = "ALTER TABLE manifest_entries ADD COLUMN $col_name $col_type;";
        echo $sql . "\n";
    }
    echo "</pre>";

    echo "<hr>";
    echo "<p><strong>Vrei să adaugi automat coloanele? Șterge comentariul de mai jos:</strong></p>";

    // UNCOMMENT pentru a adăuga automat coloanele
    /*
    foreach ($missing_columns as $col_name => $col_type) {
        try {
            $sql = "ALTER TABLE manifest_entries ADD COLUMN $col_name $col_type";
            $conn->query($sql);
            echo "<p style='color: green;'>✅ Adăugat: $col_name</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Eroare la $col_name: " . $e->getMessage() . "</p>";
        }
    }
    */
} else {
    echo "<h2 style='color: green;'>✅ Toate coloanele necesare există!</h2>";
}
?>
