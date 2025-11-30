<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

// Verifică autentificare și permisiuni admin
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Neautentificat']);
    exit;
}

// Verifică dacă este admin
$user = dbFetchOne("SELECT is_admin FROM users WHERE id = ?", [$_SESSION['user_id']]);
if (!$user || !$user['is_admin']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acces interzis. Doar administratorii pot șterge manifeste.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Metodă nepermisă']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$manifestNumber = $data['manifest_number'] ?? '';

if (empty($manifestNumber)) {
    echo json_encode(['success' => false, 'error' => 'Număr manifest lipsă']);
    exit;
}

$conn = getDbConnection();

// Verifică dacă manifestul există
$manifest = dbFetchOne("SELECT * FROM manifests WHERE manifest_number = ? LIMIT 1", [$manifestNumber]);

if (!$manifest) {
    echo json_encode(['success' => false, 'error' => 'Manifest negăsit']);
    exit;
}

try {
    $conn->begin_transaction();

    // Șterge containerele asociate
    dbQuery("DELETE FROM manifest_entries WHERE permit_number = ?", [$manifestNumber]);

    // Șterge manifestul (toate înregistrările cu același număr)
    dbQuery("DELETE FROM manifests WHERE manifest_number = ?", [$manifestNumber]);

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => "Manifestul {$manifestNumber} și toate containerele asociate au fost șterse"
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'error' => 'Eroare la ștergere: ' . $e->getMessage()
    ]);
}
?>
