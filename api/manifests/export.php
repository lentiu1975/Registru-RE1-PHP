<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

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

// Încarcă info manifest
$manifest = dbFetchOne(
    "SELECT
        m.manifest_number,
        m.arrival_date,
        MAX(me.ship_name) as ship_name
     FROM manifests m
     LEFT JOIN manifest_entries me ON m.manifest_number = me.permit_number
     WHERE m.manifest_number = ?
     GROUP BY m.manifest_number",
    [$manifestNumber]
);

if (!$manifest) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Manifest negăsit']);
    exit;
}

// Încarcă containere
$containers = dbFetchAll(
    "SELECT
        container_number,
        seal_number,
        goods_description,
        created_at
    FROM manifest_entries
    WHERE permit_number = ?
    ORDER BY container_number ASC",
    [$manifestNumber]
);

// Creează Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Titlu
$sheet->setCellValue('A1', 'MANIFEST IMPORT - ' . $manifest['manifest_number']);
$sheet->mergeCells('A1:F1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Info manifest
$sheet->setCellValue('A2', 'Navă:');
$sheet->setCellValue('B2', $manifest['ship_name']);
$sheet->setCellValue('D2', 'Data sosire:');
$sheet->setCellValue('E2', date('d.m.Y', strtotime($manifest['arrival_date'])));
$sheet->getStyle('A2:F2')->getFont()->setBold(true);

// Header tabel
$row = 4;
$headers = ['Nr.', 'Container', 'Sigiliu', 'Descriere Marfă', 'Data Înregistrare'];
$col = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($col . $row, $header);
    $col++;
}

// Stil header
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$sheet->getStyle('A' . $row . ':E' . $row)->applyFromArray($headerStyle);

// Date
$row++;
$nr = 1;
foreach ($containers as $container) {
    $sheet->setCellValue('A' . $row, $nr++);
    $sheet->setCellValue('B' . $row, $container['container_number']);
    $sheet->setCellValue('C' . $row, $container['seal_number'] ?? '-');
    $sheet->setCellValue('D' . $row, $container['goods_description'] ?? '-');
    $sheet->setCellValue('E' . $row, date('d.m.Y H:i', strtotime($container['created_at'])));

    // Stil rând
    $sheet->getStyle('A' . $row . ':E' . $row)->getBorders()->getAllBorders()
        ->setBorderStyle(Border::BORDER_THIN);

    $row++;
}

// Lățimi coloane
$sheet->getColumnDimension('A')->setWidth(8);
$sheet->getColumnDimension('B')->setWidth(20);
$sheet->getColumnDimension('C')->setWidth(15);
$sheet->getColumnDimension('D')->setWidth(40);
$sheet->getColumnDimension('E')->setWidth(20);

// Footer
$row++;
$sheet->setCellValue('A' . $row, 'Total containere: ' . count($containers));
$sheet->getStyle('A' . $row)->getFont()->setBold(true);

// Download
$filename = 'Manifest_' . $manifest['manifest_number'] . '_' . date('Y-m-d') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
