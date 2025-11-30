<?php
/**
 * Script pentru upgrade bază de date
 * Rulează upgrade_database.sql
 */

require_once 'config/database.php';

echo "===================================\n";
echo "UPGRADE BAZĂ DE DATE\n";
echo "===================================\n\n";

// Citește fișierul SQL
$sqlFile = __DIR__ . '/upgrade_database.sql';
if (!file_exists($sqlFile)) {
    die("ERROR: Fișierul upgrade_database.sql nu a fost găsit!\n");
}

$sql = file_get_contents($sqlFile);
if ($sql === false) {
    die("ERROR: Nu s-a putut citi fișierul upgrade_database.sql!\n");
}

// Conectare la bază de date
$conn = getDbConnection();
if (!$conn) {
    die("ERROR: Nu s-a putut conecta la baza de date!\n");
}

echo "Conectat la baza de date: " . DB_NAME . "\n\n";

// Împarte SQL-ul în comenzi individuale
// Eliminăm comentariile și liniile goale
$lines = explode("\n", $sql);
$commands = [];
$currentCommand = '';

foreach ($lines as $line) {
    $line = trim($line);

    // Sare peste comentarii și linii goale
    if (empty($line) || strpos($line, '--') === 0) {
        continue;
    }

    $currentCommand .= $line . ' ';

    // Dacă linia se termină cu ;, atunci avem o comandă completă
    if (substr($line, -1) === ';') {
        $commands[] = trim($currentCommand);
        $currentCommand = '';
    }
}

// Rulează fiecare comandă
$success = 0;
$failed = 0;
$total = count($commands);

echo "Se procesează $total comenzi SQL...\n\n";

foreach ($commands as $i => $command) {
    if (empty(trim($command))) {
        continue;
    }

    // Extrage prima parte a comenzii pentru afișare
    $commandPreview = substr($command, 0, 100);
    if (strlen($command) > 100) {
        $commandPreview .= '...';
    }

    echo "[" . ($i + 1) . "/$total] " . $commandPreview . "\n";

    if ($conn->query($command) === TRUE) {
        $success++;
        echo "  ✓ SUCCESS\n";
    } else {
        // Ignorăm erorile pentru comenzi care pot eșua (ex: ALTER TABLE IF NOT EXISTS)
        $error = $conn->error;
        if (stripos($error, 'Duplicate column') !== false ||
            stripos($error, 'Duplicate key') !== false ||
            stripos($error, 'already exists') !== false) {
            echo "  ⚠ SKIPPED (already exists)\n";
            $success++;
        } else {
            $failed++;
            echo "  ✗ ERROR: " . $error . "\n";
        }
    }
    echo "\n";
}

$conn->close();

// Rezumat
echo "===================================\n";
echo "REZUMAT UPGRADE\n";
echo "===================================\n";
echo "Total comenzi: $total\n";
echo "Succes: $success\n";
echo "Eșuate: $failed\n";
echo "\n";

if ($failed === 0) {
    echo "✓ UPGRADE COMPLETAT CU SUCCES!\n";
} else {
    echo "⚠ UPGRADE COMPLETAT CU ERORI!\n";
    echo "Verifică erorile de mai sus.\n";
}

echo "===================================\n";
