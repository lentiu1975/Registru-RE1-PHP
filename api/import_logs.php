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

// Total logs
$totalResult = dbFetchOne("SELECT COUNT(*) as total FROM import_logs");
$total = $totalResult['total'] ?? 0;

// Fetch logs with user info
$logs = dbFetchAll(
    "SELECT il.*, u.username
     FROM import_logs il
     LEFT JOIN users u ON il.user_id = u.id
     ORDER BY il.created_at DESC
     LIMIT ? OFFSET ?",
    [$perPage, $offset]
);

jsonResponse([
    'data' => $logs,
    'pagination' => [
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => ceil($total / $perPage)
    ]
]);
