<?php
/**
 * Export manifest ca XLS (HTML table format - Excel îl deschide nativ)
 * Ordinea conform model import.xlsx
 */
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Verifică autentificare
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Neautentificat']);
    exit;
}

$manifestNumber = $_GET['manifest_number'] ?? '';

if (empty($manifestNumber)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Număr manifest lipsă']);
    exit;
}

$conn = getDbConnection();

// Încarcă containere cu toate coloanele necesare în ordinea corectă conform model import.xlsx
$containers = dbFetchAll(
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

if (empty($containers)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Nu s-au găsit date pentru acest manifest']);
    exit;
}

// Export XLS (HTML table format - Excel îl recunoaște)
$filename = 'Manifest_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $manifestNumber) . '_' . date('Y-m-d') . '.xls';

header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Start HTML table pentru Excel
echo '<!DOCTYPE html>';
echo '<html><head><meta charset="UTF-8">';
echo '<style>
    table { border-collapse: collapse; font-family: Arial, sans-serif; font-size: 10pt; }
    th { background-color: #4472C4; color: white; font-weight: bold; text-align: center; padding: 8px 5px; border: 1px solid #2F5496; }
    td { padding: 5px; border: 1px solid #B4C6E7; }
    .row-even { background-color: #D6DCE5; }
    .row-odd { background-color: #FFFFFF; }
</style>';
echo '</head><body>';
echo '<table>';

// Header row - cu prima literă mare și text pe 2 rânduri unde e cazul
echo '<tr>';
$headers = [
    'Numar<br>Manifest',
    'Numar<br>Permis',
    'Numar<br>Pozitie',
    'Cerere<br>Operatiune',
    'Data<br>Inregistrare',
    'Container',
    'Descriere<br>Marfa',
    'Numar<br>Colete',
    'Greutate<br>Bruta',
    'Tip<br>Operatiune',
    'Nume<br>Nava',
    'Pavilion<br>Nava',
    'Numar<br>Sumara'
];
foreach ($headers as $header) {
    echo '<th>' . $header . '</th>';
}
echo '</tr>';

// Data rows cu zebra stripes
$rowNum = 0;
foreach ($containers as $c) {
    $rowClass = ($rowNum % 2 == 0) ? 'row-odd' : 'row-even';
    echo '<tr class="' . $rowClass . '">';
    echo '<td>' . htmlspecialchars($c['numar_manifest'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($c['permit_number'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($c['position_number'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($c['operation_request'] ?? '') . '</td>';
    echo '<td>' . ($c['data_inregistrare'] ? date('d.m.Y', strtotime($c['data_inregistrare'])) : '') . '</td>';
    echo '<td>' . htmlspecialchars($c['container_number'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($c['goods_description'] ?? '') . '</td>';
    echo '<td style="text-align: right;">' . htmlspecialchars($c['packages'] ?? '') . '</td>';
    echo '<td style="text-align: right;">' . htmlspecialchars($c['weight'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($c['operation_type'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($c['ship_name'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($c['ship_flag'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($c['summary_number'] ?? '') . '</td>';
    echo '</tr>';
    $rowNum++;
}

echo '</table>';
echo '</body></html>';
exit;
