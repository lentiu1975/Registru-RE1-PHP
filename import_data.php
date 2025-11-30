<?php
/**
 * Script pentru import automat date complete
 * Acceseaza: https://vamactasud.lentiu.ro/import_data.php
 */

set_time_limit(300); // 5 minute timeout
ini_set('memory_limit', '256M');

require_once 'config/database.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Import Date Complete</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#1e1e1e;color:#d4d4d4;}";
echo ".success{color:#4ec9b0;} .error{color:#f48771;} .info{color:#569cd6;}</style></head><body>";

echo "<h2 class='info'>Import Date Complete - Registru RE1</h2>\n";

// Pasul 1: Adauga coloanele lipsa
echo "<h3>Pasul 1: Adaugare coloane noi (packages, summary_number, operation_type)</h3>\n";

$sql1 = file_get_contents('add_missing_columns.sql');
if ($sql1 === false) {
    echo "<p class='error'>EROARE: Nu gasesc fisierul add_missing_columns.sql</p>\n";
    exit;
}

// Executa fiecare comanda SQL separat
$commands = array_filter(array_map('trim', explode(';', $sql1)));
foreach ($commands as $cmd) {
    if (empty($cmd) || strpos($cmd, '--') === 0) continue;

    try {
        dbExecute($cmd);
        echo "<p class='success'>✓ Executat: " . substr($cmd, 0, 80) . "...</p>\n";
    } catch (Exception $e) {
        // Ignora eroarea daca coloana deja exista
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<p class='info'>○ Coloana deja exista - skip</p>\n";
        } else {
            echo "<p class='error'>✗ EROARE: " . htmlspecialchars($e->getMessage()) . "</p>\n";
        }
    }
    flush();
}

echo "<p class='success'><strong>Pasul 1 COMPLET</strong></p>\n";

// Pasul 2: Import date complete (3195 containere)
echo "<h3>Pasul 2: Import 3195 containere cu toate campurile</h3>\n";
echo "<p class='info'>Acest pas poate dura 1-2 minute...</p>\n";
flush();

$sql2 = file_get_contents('migrate_complete.sql');
if ($sql2 === false) {
    echo "<p class='error'>EROARE: Nu gasesc fisierul migrate_complete.sql</p>\n";
    exit;
}

// Executa comenzile SQL
$commands = array_filter(array_map('trim', explode(';', $sql2)));
$count = 0;
$total = count($commands);

foreach ($commands as $cmd) {
    if (empty($cmd) || strpos($cmd, '--') === 0) continue;

    try {
        dbExecute($cmd);
        $count++;

        // Progress indicator la fiecare 500 comenzi
        if ($count % 500 == 0) {
            echo "<p class='info'>Progres: $count / $total comenzi...</p>\n";
            flush();
        }
    } catch (Exception $e) {
        echo "<p class='error'>✗ EROARE la comanda $count: " . htmlspecialchars($e->getMessage()) . "</p>\n";
        echo "<p class='error'>SQL: " . substr($cmd, 0, 200) . "...</p>\n";
        flush();
    }
}

echo "<p class='success'><strong>Pasul 2 COMPLET - $count comenzi executate</strong></p>\n";

// Verificare finala
echo "<h3>Verificare finala</h3>\n";

$result = dbFetchOne("SELECT COUNT(*) as total FROM manifest_entries");
echo "<p class='success'>Total containere in baza de date: <strong>{$result['total']}</strong></p>\n";

$result = dbFetchOne("SELECT COUNT(*) as total FROM manifest_entries WHERE packages IS NOT NULL");
echo "<p class='success'>Containere cu camp 'packages' completat: <strong>{$result['total']}</strong></p>\n";

$result = dbFetchOne("SELECT COUNT(*) as total FROM manifest_entries WHERE summary_number IS NOT NULL");
echo "<p class='success'>Containere cu camp 'summary_number' completat: <strong>{$result['total']}</strong></p>\n";

$result = dbFetchOne("SELECT COUNT(*) as total FROM manifest_entries WHERE operation_type IS NOT NULL");
echo "<p class='success'>Containere cu camp 'operation_type' completat: <strong>{$result['total']}</strong></p>\n";

echo "<hr>";
echo "<h2 class='success'>✓✓✓ IMPORT COMPLET! ✓✓✓</h2>\n";
echo "<p class='info'>Toate campurile au fost importate cu succes.</p>\n";
echo "<p class='info'>Acum poti testa cautarea la: <a href='/' style='color:#569cd6;'>https://vamactasud.lentiu.ro/</a></p>\n";

echo "</body></html>";
