<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

echo "<pre>";
echo "=== CONTAINER_TYPES TABLE STRUCTURE ===\n\n";

$conn = getDbConnection();

// Show table structure
$result = $conn->query("DESCRIBE container_types");
echo "Columns:\n";
while ($row = $result->fetch_assoc()) {
    echo "  {$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Key']}\n";
}

// Show first 5 rows
echo "\n=== SAMPLE DATA (first 5) ===\n";
$data = $conn->query("SELECT * FROM container_types LIMIT 5");
while ($row = $data->fetch_assoc()) {
    print_r($row);
}

// Check types in manifest not in container_types (using correct column name)
echo "\n=== TYPES IN MANIFEST 159 ===\n";
$types = $conn->query("
    SELECT DISTINCT container_type, COUNT(*) as cnt
    FROM manifest_entries
    WHERE numar_manifest = '159'
    GROUP BY container_type
    ORDER BY cnt DESC
");
while ($row = $types->fetch_assoc()) {
    echo "{$row['container_type']}: {$row['cnt']}\n";
}

$conn->close();
echo "</pre>";
?>
