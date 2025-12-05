<?php
/**
 * Script pentru adăugarea coloanei pavilion_id la tabela ships
 */
require_once 'config/database.php';

$conn = getDbConnection();

echo "=== ADĂUGARE PAVILION LA NAVE ===\n\n";

// 1. Verifică dacă coloana există deja
$result = $conn->query("SHOW COLUMNS FROM ships LIKE 'pavilion_id'");
if ($result->num_rows > 0) {
    echo "Coloana pavilion_id există deja!\n";
} else {
    // Adaugă coloana
    $conn->query("ALTER TABLE ships ADD COLUMN pavilion_id INT NULL AFTER name");
    echo "✅ Coloana pavilion_id adăugată!\n";

    // Adaugă foreign key
    $conn->query("ALTER TABLE ships ADD CONSTRAINT fk_ships_pavilion FOREIGN KEY (pavilion_id) REFERENCES pavilions(id) ON DELETE SET NULL");
    echo "✅ Foreign key adăugat!\n";
}

// 2. Sincronizează pavilionul pe baza ship_flag din manifest_entries
echo "\n--- Sincronizare automată pavilion din manifest_entries ---\n";

// Pentru fiecare navă, găsește pavilionul folosit în manifest_entries
$ships = $conn->query("SELECT id, name FROM ships");
$updated = 0;

while ($ship = $ships->fetch_assoc()) {
    // Găsește cel mai frecvent pavilion pentru această navă
    $flagResult = $conn->query("
        SELECT ship_flag, COUNT(*) as cnt
        FROM manifest_entries
        WHERE ship_name = '" . $conn->real_escape_string($ship['name']) . "'
          AND ship_flag IS NOT NULL AND ship_flag != ''
        GROUP BY ship_flag
        ORDER BY cnt DESC
        LIMIT 1
    ");

    if ($flagRow = $flagResult->fetch_assoc()) {
        $flagName = $flagRow['ship_flag'];

        // Găsește pavilionul corespunzător
        $pavilionResult = $conn->query("
            SELECT id FROM pavilions
            WHERE name LIKE '%" . $conn->real_escape_string($flagName) . "%'
               OR country_name LIKE '%" . $conn->real_escape_string($flagName) . "%'
            LIMIT 1
        ");

        if ($pavilion = $pavilionResult->fetch_assoc()) {
            $conn->query("UPDATE ships SET pavilion_id = {$pavilion['id']} WHERE id = {$ship['id']}");
            echo "  {$ship['name']} -> pavilion {$flagName} (ID: {$pavilion['id']})\n";
            $updated++;
        }
    }
}

echo "\n✅ Actualizate $updated nave cu pavilion!\n";

// 3. Afișează rezultatul
echo "\n--- Nave cu pavilion ---\n";
$result = $conn->query("
    SELECT s.name, p.name as pavilion_name
    FROM ships s
    LEFT JOIN pavilions p ON s.pavilion_id = p.id
    ORDER BY s.name
    LIMIT 20
");

while ($row = $result->fetch_assoc()) {
    echo "  {$row['name']} - {$row['pavilion_name']}\n";
}

$conn->close();
echo "\n🎉 DONE!\n";
?>
