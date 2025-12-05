<?php
/**
 * API pentru Export Date în CSV - ordinea conform model import.xlsx
 */

session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Verifică autentificare
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo 'Neautentificat';
    exit;
}

$manifestNumber = $_GET['manifest'] ?? '';
$format = $_GET['format'] ?? 'csv';

if (empty($manifestNumber)) {
    http_response_code(400);
    echo 'Număr manifest lipsă';
    exit;
}

// Fetch data - ordinea conform model import.xlsx
$entries = dbFetchAll(
    "SELECT
        numar_manifest,
        permit_number,
        position_number,
        operation_request,
        data_inregistrare,
        container_number,
        goods_description,
        packages,
        weight,
        operation_type,
        ship_name,
        ship_flag,
        summary_number
    FROM manifest_entries
    WHERE permit_number = ?
    ORDER BY numar_curent ASC, id ASC",
    [$manifestNumber]
);

if (empty($entries)) {
    http_response_code(404);
    echo 'Nu s-au găsit date pentru acest manifest';
    exit;
}

// Export CSV
$filename = 'export_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $manifestNumber) . '_' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, no-store, must-revalidate');

$output = fopen('php://output', 'w');

// BOM pentru Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Header row - ordinea conform model import.xlsx
$headers = [
    'numar manifest',
    'numar permis',
    'numar pozitie',
    'cerere operatiune',
    'data inregistrare',
    'container',
    'descriere marfa',
    'numar colete',
    'greutate bruta',
    'tip operatiune',
    'nume nava',
    'pavilion nava',
    'numar sumara'
];

fputcsv($output, $headers, ';');

// Data rows
foreach ($entries as $entry) {
    $row = [
        $entry['numar_manifest'],
        $entry['permit_number'],
        $entry['position_number'],
        $entry['operation_request'],
        $entry['data_inregistrare'],
        $entry['container_number'],
        $entry['goods_description'],
        $entry['packages'],
        $entry['weight'],
        $entry['operation_type'],
        $entry['ship_name'],
        $entry['ship_flag'],
        $entry['summary_number']
    ];

    fputcsv($output, $row, ';');
}

fclose($output);
exit;
