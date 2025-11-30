<?php
/**
 * API pentru Import Excel
 */

session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Verifică autentificare
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Neautentificat']);
    exit;
}

$conn = getDbConnection();
$userId = $_SESSION['user_id'];

// Verifică dacă este POST cu fișier
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Metodă nepermisă']);
    exit;
}

// Verifică fișier
if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'Fișier lipsă sau eroare la upload']);
    exit;
}

$file = $_FILES['excel_file'];
$templateId = $_POST['template_id'] ?? null;
$manifestNumber = $_POST['manifest_number'] ?? '';
$shipName = $_POST['ship_name'] ?? '';
$arrivalDate = $_POST['arrival_date'] ?? '';

// Validare
if (empty($manifestNumber) || empty($shipName) || empty($arrivalDate)) {
    echo json_encode(['success' => false, 'error' => 'Manifest, navă și dată sunt obligatorii']);
    exit;
}

// Verifică extensie fișier
$fileName = $file['name'];
$fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if (!in_array($fileExt, ['xls', 'xlsx'])) {
    echo json_encode(['success' => false, 'error' => 'Doar fișiere XLS sau XLSX sunt permise']);
    exit;
}

// Încarcă template
$template = null;
if ($templateId) {
    $template = dbFetchOne("SELECT * FROM import_templates WHERE id = ?", [$templateId]);
} else {
    // Template default
    $template = dbFetchOne("SELECT * FROM import_templates WHERE is_default = 1 LIMIT 1");
}

if (!$template) {
    echo json_encode(['success' => false, 'error' => 'Template import negăsit']);
    exit;
}

$columnMappings = json_decode($template['column_mappings'], true);

try {
    // Procesare Excel
    require_once '../vendor/autoload.php';

    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file['tmp_name']);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();

    // Skip header row
    array_shift($rows);

    $imported = 0;
    $errors = [];

    // Începe tranzacție
    $conn->begin_transaction();

    // Creează/actualizează manifestul
    $manifestExists = dbFetchOne("SELECT id FROM manifests WHERE manifest_number = ?", [$manifestNumber]);

    if (!$manifestExists) {
        dbQuery(
            "INSERT INTO manifests (manifest_number, ship_name, arrival_date, created_by) VALUES (?, ?, ?, ?)",
            [$manifestNumber, $shipName, $arrivalDate, $userId]
        );
        $manifestId = $conn->insert_id;
    } else {
        $manifestId = $manifestExists['id'];
    }

    // Import containere
    foreach ($rows as $index => $row) {
        // Skip linii goale
        if (empty(array_filter($row))) {
            continue;
        }

        $rowNum = $index + 2; // +2 pentru header și index 0-based

        // Mapare coloane
        $containerNumber = isset($columnMappings['container_number']) ?
            ($row[ord($columnMappings['container_number']) - ord('A')] ?? '') : '';
        $sealNumber = isset($columnMappings['seal_number']) ?
            ($row[ord($columnMappings['seal_number']) - ord('A')] ?? '') : '';
        $goodsDescription = isset($columnMappings['goods_description']) ?
            ($row[ord($columnMappings['goods_description']) - ord('A')] ?? '') : '';

        // Validare container
        if (empty($containerNumber)) {
            $errors[] = "Rând {$rowNum}: Container lipsă";
            continue;
        }

        // Verifică duplicate
        $exists = dbFetchOne(
            "SELECT id FROM manifest_entries WHERE manifest_number = ? AND container_number = ?",
            [$manifestNumber, $containerNumber]
        );

        if ($exists) {
            $errors[] = "Rând {$rowNum}: Container {$containerNumber} deja există";
            continue;
        }

        // Insert
        dbQuery(
            "INSERT INTO manifest_entries (manifest_number, ship_name, arrival_date, container_number, seal_number, goods_description)
             VALUES (?, ?, ?, ?, ?, ?)",
            [$manifestNumber, $shipName, $arrivalDate, $containerNumber, $sealNumber, $goodsDescription]
        );

        $imported++;
    }

    // Log import
    dbQuery(
        "INSERT INTO import_logs (user_id, template_id, filename, rows_imported, rows_failed, created_at)
         VALUES (?, ?, ?, ?, ?, NOW())",
        [$userId, $template['id'], $fileName, $imported, count($errors)]
    );

    $conn->commit();

    echo json_encode([
        'success' => true,
        'imported' => $imported,
        'errors' => $errors,
        'manifest_number' => $manifestNumber
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'error' => 'Eroare la procesare: ' . $e->getMessage()
    ]);
}
?>
