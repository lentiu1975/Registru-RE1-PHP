<?php
/**
 * API pentru descărcarea fișierului model de import
 * Returnează fișierul Excel cu structura corectă pentru import standard
 */

session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Verifică autentificare
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo "Neautentificat";
    exit;
}

// Calea către fișierul model
$modelFile = __DIR__ . '/../model import.xlsx';

if (!file_exists($modelFile)) {
    http_response_code(404);
    echo "Fișierul model nu a fost găsit";
    exit;
}

// Setează headerele pentru descărcare
$filename = 'Model_Import_Standard.xlsx';
header('Content-Description: File Transfer');
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($modelFile));

// Trimite fișierul
readfile($modelFile);
exit;
