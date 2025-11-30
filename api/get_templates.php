<?php
/**
 * API pentru obținere template-uri import
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

$templates = dbFetchAll("SELECT * FROM import_templates ORDER BY is_default DESC, name ASC");

echo json_encode([
    'success' => true,
    'templates' => $templates
]);
?>
