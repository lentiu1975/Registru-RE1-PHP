<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

// Verifică autentificare
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Neautentificat']);
    exit;
}

// Primește datele JSON
$data = json_decode(file_get_contents('php://input'), true);

$id = $data['id'] ?? null;

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'ID lipsă']);
    exit;
}

$conn = getDbConnection();

// Câmpurile care pot fi editate
$updates = [];
$params = [];

$editableFields = [
    'container_number', 'container_type', 'model_container', 'packages', 'weight',
    'goods_description', 'operation_type', 'ship_name', 'ship_flag',
    'summary_number', 'linie_maritima', 'observatii', 'permit_number',
    'position_number', 'operation_request', 'data_inregistrare'
];

foreach ($editableFields as $field) {
    if (isset($data[$field])) {
        $updates[] = "$field = ?";
        $params[] = $data[$field];
    }
}

// Regenerează model_container dacă s-au modificat container_number sau container_type
if (isset($data['container_number']) || isset($data['container_type'])) {
    $current = dbFetchOne("SELECT container_number, container_type FROM manifest_entries WHERE id = ?", [$id]);
    $containerNum = $data['container_number'] ?? $current['container_number'];
    $containerType = $data['container_type'] ?? $current['container_type'];

    if ($containerNum && $containerType) {
        $modelContainer = substr($containerNum, 0, 4) . $containerType;
        $updates[] = "model_container = ?";
        $params[] = $modelContainer;
    }
}

if (empty($updates)) {
    echo json_encode(['success' => false, 'error' => 'Niciun câmp de actualizat']);
    exit;
}

$params[] = $id;
$sql = "UPDATE manifest_entries SET " . implode(', ', $updates) . " WHERE id = ?";

try {
    $conn->prepare($sql)->execute($params);
    echo json_encode(['success' => true, 'message' => 'Actualizat cu succes']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
