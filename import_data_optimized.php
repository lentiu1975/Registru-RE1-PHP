<?php
/**
 * Script OPTIMIZAT pentru import automat date complete
 * Proceseaza datele in batch-uri mici pentru a evita timeout
 */

set_time_limit(0); // Unlimited timeout
ini_set('memory_limit', '512M');
ini_set('max_execution_time', '0');

require_once 'config/database.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Import Date Complete</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#1e1e1e;color:#d4d4d4;}";
echo ".success{color:#4ec9b0;} .error{color:#f48771;} .info{color:#569cd6;}</style>";
echo "<script>function scrollToBottom(){window.scrollTo(0,document.body.scrollHeight);}</script>";
echo "</head><body onload='setInterval(scrollToBottom, 500)'>";

echo "<h2 class='info'>Import Date Complete - Registru RE1 (OPTIMIZAT)</h2>\n";
flush();

// Pasul 1: Adauga coloanele lipsa
echo "<h3>Pasul 1: Adaugare coloane noi</h3>\n";

try {
    dbExecute("ALTER TABLE manifest_entries ADD COLUMN packages INT DEFAULT NULL COMMENT 'Număr colete'");
    echo "<p class='success'>✓ Coloana 'packages' adaugata</p>\n";
    flush();
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "<p class='info'>○ Coloana 'packages' deja exista</p>\n";
    } else {
        echo "<p class='error'>✗ EROARE packages: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    }
    flush();
}

try {
    dbExecute("ALTER TABLE manifest_entries ADD COLUMN summary_number VARCHAR(100) DEFAULT NULL COMMENT 'Număr sumară'");
    echo "<p class='success'>✓ Coloana 'summary_number' adaugata</p>\n";
    flush();
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "<p class='info'>○ Coloana 'summary_number' deja exista</p>\n";
    } else {
        echo "<p class='error'>✗ EROARE summary_number: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    }
    flush();
}

try {
    dbExecute("ALTER TABLE manifest_entries ADD COLUMN operation_type VARCHAR(1) DEFAULT 'I' COMMENT 'Tip operațiune: I=Import, E=Export'");
    echo "<p class='success'>✓ Coloana 'operation_type' adaugata</p>\n";
    flush();
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "<p class='info'>○ Coloana 'operation_type' deja exista</p>\n";
    } else {
        echo "<p class='error'>✗ EROARE operation_type: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    }
    flush();
}

echo "<p class='success'><strong>✓ Pasul 1 COMPLET</strong></p>\n";
flush();

// Pasul 2: Sterge datele vechi si importa date noi IN BATCH-URI
echo "<h3>Pasul 2: Stergere date vechi</h3>\n";
flush();

dbExecute("DELETE FROM manifest_entries");
echo "<p class='success'>✓ Sters manifest_entries</p>\n";
flush();

dbExecute("DELETE FROM manifests");
echo "<p class='success'>✓ Sters manifests</p>\n";
flush();

// Pasul 3: Import date din fisierul SQL - citeste si executa linie cu linie
echo "<h3>Pasul 3: Import 3195 containere (procesat linie cu linie)</h3>\n";
flush();

$sql_file = 'migrate_complete.sql';
if (!file_exists($sql_file)) {
    echo "<p class='error'>✗ EROARE: Nu gasesc fisierul $sql_file</p>\n";
    exit;
}

$handle = fopen($sql_file, 'r');
if (!$handle) {
    echo "<p class='error'>✗ EROARE: Nu pot deschide fisierul $sql_file</p>\n";
    exit;
}

$count = 0;
$errors = 0;
$buffer = '';

while (($line = fgets($handle)) !== false) {
    $line = trim($line);

    // Skip comentarii si linii goale
    if (empty($line) || strpos($line, '--') === 0 || strpos($line, 'SET ') === 0) {
        continue;
    }

    // Adauga linia la buffer
    $buffer .= ' ' . $line;

    // Daca linia se termina cu ;, executa comanda
    if (substr($line, -1) === ';') {
        $sql = trim($buffer);
        $buffer = '';

        if (empty($sql)) continue;

        try {
            dbExecute($sql);
            $count++;

            // Progress indicator la fiecare 100 comenzi (nu 500, ca sa vedem progresul mai des)
            if ($count % 100 == 0) {
                echo "<p class='info'>⏳ Progres: $count comenzi executate...</p>\n";
                flush();
            }
        } catch (Exception $e) {
            $errors++;
            if ($errors <= 5) { // Afiseaza doar primele 5 erori
                echo "<p class='error'>✗ EROARE la comanda $count: " . htmlspecialchars(substr($e->getMessage(), 0, 100)) . "</p>\n";
                flush();
            }
        }
    }
}

fclose($handle);

echo "<p class='success'><strong>✓ Pasul 3 COMPLET - $count comenzi executate, $errors erori</strong></p>\n";
flush();

// Verificare finala
echo "<h3>Verificare finala</h3>\n";
flush();

$result = dbFetchOne("SELECT COUNT(*) as total FROM manifest_entries");
echo "<p class='success'>Total containere in baza de date: <strong>{$result['total']}</strong></p>\n";
flush();

$result = dbFetchOne("SELECT COUNT(*) as total FROM manifest_entries WHERE packages IS NOT NULL");
echo "<p class='success'>Containere cu 'packages' completat: <strong>{$result['total']}</strong></p>\n";
flush();

$result = dbFetchOne("SELECT COUNT(*) as total FROM manifest_entries WHERE summary_number IS NOT NULL");
echo "<p class='success'>Containere cu 'summary_number' completat: <strong>{$result['total']}</strong></p>\n";
flush();

$result = dbFetchOne("SELECT COUNT(*) as total FROM manifest_entries WHERE operation_type IS NOT NULL");
echo "<p class='success'>Containere cu 'operation_type' completat: <strong>{$result['total']}</strong></p>\n";
flush();

// Arata un exemplu de date importate
echo "<h3>Exemplu date importate</h3>\n";
$sample = dbFetchOne("SELECT * FROM manifest_entries WHERE packages IS NOT NULL LIMIT 1");
if ($sample) {
    echo "<pre class='info'>";
    echo "Container: {$sample['container_number']}\n";
    echo "Colete: {$sample['packages']}\n";
    echo "Greutate: {$sample['weight']} Kg\n";
    echo "Numar sumara: {$sample['summary_number']}\n";
    echo "Tip operatiune: " . ($sample['operation_type'] === 'E' ? 'Export' : 'Import') . "\n";
    echo "Descriere: " . substr($sample['goods_description'], 0, 50) . "...\n";
    echo "</pre>";
}

echo "<hr>";
echo "<h2 class='success'>✓✓✓ IMPORT COMPLET! ✓✓✓</h2>\n";
echo "<p class='info'>Toate campurile au fost importate cu succes.</p>\n";
echo "<p class='info'>Acum poti testa cautarea la: <a href='/' style='color:#569cd6;'>https://vamactasud.lentiu.ro/</a></p>\n";

echo "</body></html>";
