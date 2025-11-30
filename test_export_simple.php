<?php
session_start();
$_SESSION['user_id'] = 1;

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test Export Excel</h1>";

// Test 1: Verifică dacă vendor/autoload.php există
echo "<h2>1. Verificare vendor/autoload.php</h2>";
if (file_exists('vendor/autoload.php')) {
    echo "<p style='color: green;'>✓ vendor/autoload.php EXISTĂ</p>";
    require_once 'vendor/autoload.php';
} else {
    echo "<p style='color: red;'>✗ vendor/autoload.php NU EXISTĂ!</p>";
    echo "<p>Trebuie să rulezi: <code>composer require phpoffice/phpspreadsheet</code></p>";
    exit;
}

// Test 2: Verifică dacă PhpSpreadsheet este disponibil
echo "<h2>2. Verificare PhpSpreadsheet</h2>";
try {
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    echo "<p style='color: green;'>✓ PhpSpreadsheet se poate instanția!</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Eroare: " . $e->getMessage() . "</p>";
    exit;
}

// Test 3: Creează un Excel simplu
echo "<h2>3. Test creare Excel simplu</h2>";
try {
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setCellValue('A1', 'Test');
    $sheet->setCellValue('B1', 'Data');

    echo "<p style='color: green;'>✓ Excel creat cu succes!</p>";

    // Salvează ca fișier temporar
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $tempFile = sys_get_temp_dir() . '/test_excel_' . time() . '.xlsx';
    $writer->save($tempFile);

    if (file_exists($tempFile)) {
        $fileSize = filesize($tempFile);
        echo "<p style='color: green;'>✓ Fișier Excel salvat: $tempFile ($fileSize bytes)</p>";
        unlink($tempFile); // Șterge fișierul temp
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Eroare la creare Excel: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Test 4: Încarcă config și functions
echo "<h2>4. Test config și functions</h2>";
if (file_exists('config/database.php')) {
    echo "<p style='color: green;'>✓ config/database.php există</p>";
} else {
    echo "<p style='color: red;'>✗ config/database.php NU există</p>";
}

if (file_exists('includes/functions.php')) {
    echo "<p style='color: green;'>✓ includes/functions.php există</p>";
} else {
    echo "<p style='color: red;'>✗ includes/functions.php NU există</p>";
}

echo "<hr>";
echo "<h2>5. Test Export API Direct</h2>";
echo "<p><a href='api/manifests/export.php?manifest_number=153' target='_blank'>Click aici pentru a testa export API</a></p>";
?>
