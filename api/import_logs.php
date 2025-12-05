<?php
/**
 * API pentru vizualizare log-uri import
 */

header('Content-Type: application/json; charset=utf-8');
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    jsonResponse(['error' => 'Autentificare necesară'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    jsonResponse(['error' => 'Metodă nepermisă'], 405);
}

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = isset($_GET['per_page']) ? min(100, max(10, intval($_GET['per_page']))) : 20;
$offset = ($page - 1) * $perPage;

try {
    // Total logs
    $totalResult = dbFetchOne("SELECT COUNT(*) as total FROM import_logs");
    $total = $totalResult['total'] ?? 0;

    // Fetch logs - structura: id, filename, rows_imported, rows_failed, status, error_message, created_at
    $logs = dbFetchAll(
        "SELECT * FROM import_logs ORDER BY created_at DESC LIMIT ? OFFSET ?",
        [$perPage, $offset]
    );

    jsonResponse([
        'data' => $logs ?: [],
        'pagination' => [
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $total > 0 ? ceil($total / $perPage) : 1
        ]
    ]);
} catch (Exception $e) {
    jsonResponse([
        'data' => [],
        'pagination' => [
            'total' => 0,
            'page' => 1,
            'per_page' => $perPage,
            'total_pages' => 1
        ],
        'message' => 'Nu există istoric de import'
    ]);
}
