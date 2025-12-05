<?php
/**
 * API pentru ultimul manifest actualizat
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Verifică autentificare
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['manifest_number' => 'N/A', 'ship_name' => 'N/A', 'arrival_date' => null]);
    exit;
}

// Query pentru ultimul manifest (cel mai mare număr de manifest)
$sql = "SELECT m.manifest_number, m.arrival_date, me.ship_name
        FROM manifests m
        LEFT JOIN manifest_entries me ON m.id = me.manifest_id
        WHERE m.manifest_number IS NOT NULL
          AND m.manifest_number != ''
          AND me.ship_name IS NOT NULL
          AND me.ship_name != 'N/A'
        ORDER BY CAST(m.manifest_number AS UNSIGNED) DESC
        LIMIT 1";

$result = dbFetchOne($sql);

if ($result) {
    jsonResponse([
        'manifest_number' => $result['manifest_number'],
        'ship_name' => $result['ship_name'],
        'arrival_date' => $result['arrival_date']
    ]);
} else {
    jsonResponse([
        'manifest_number' => 'N/A',
        'ship_name' => 'N/A',
        'arrival_date' => null
    ]);
}
