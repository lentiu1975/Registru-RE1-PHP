<?php
/**
 * API pentru ultimele manifeste actualizate
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Verifică autentificare
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['manifests' => []]);
    exit;
}

// Query pentru ultimele 3 manifeste (cele mai mari numere de manifest)
$sql = "SELECT DISTINCT m.manifest_number, m.arrival_date,
        (SELECT me.ship_name FROM manifest_entries me WHERE me.numar_manifest = m.manifest_number AND me.ship_name IS NOT NULL AND me.ship_name != '' LIMIT 1) as ship_name
        FROM manifests m
        WHERE m.manifest_number IS NOT NULL
          AND m.manifest_number != ''
        ORDER BY CAST(m.manifest_number AS UNSIGNED) DESC
        LIMIT 3";

$results = dbFetchAll($sql);

if ($results && count($results) > 0) {
    // Filtrează rezultatele care au ship_name valid
    $manifests = [];
    foreach ($results as $row) {
        if (!empty($row['ship_name'])) {
            $manifests[] = [
                'manifest_number' => $row['manifest_number'],
                'ship_name' => $row['ship_name'],
                'arrival_date' => $row['arrival_date']
            ];
        }
    }
    jsonResponse(['manifests' => $manifests]);
} else {
    jsonResponse(['manifests' => []]);
}
