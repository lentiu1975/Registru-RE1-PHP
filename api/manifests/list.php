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

$conn = getDbConnection();

// Parametri căutare și paginare
$search = $_GET['search'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 50;
$offset = ($page - 1) * $limit;

// Construiește query
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "m.manifest_number LIKE ?";
    $params[] = "%{$search}%";
}

if (!empty($dateFrom)) {
    $where[] = "m.arrival_date >= ?";
    $params[] = $dateFrom;
}

if (!empty($dateTo)) {
    $where[] = "m.arrival_date <= ?";
    $params[] = $dateTo;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Query simplu și rapid pentru total - doar pe manifests
$totalQuery = "SELECT COUNT(DISTINCT m.manifest_number) as total
               FROM manifests m
               {$whereClause}";

$totalStmt = $conn->prepare($totalQuery);
if (!empty($params)) {
    $totalStmt->bind_param(str_repeat('s', count($params)), ...$params);
}
$totalStmt->execute();
$totalResult = $totalStmt->get_result();
$totalRow = $totalResult->fetch_assoc();
$total = $totalRow['total'] ?? 0;
$totalStmt->close();

// Query optimizat - folosim subquery pentru a evita JOIN-uri mari
$dataQuery = "SELECT
    m.manifest_number,
    m.arrival_date,
    (SELECT ship_name FROM manifest_entries WHERE permit_number = m.manifest_number LIMIT 1) as ship_name,
    (SELECT COUNT(*) FROM manifest_entries WHERE permit_number = m.manifest_number) as container_count,
    (SELECT username FROM users WHERE id = m.created_by LIMIT 1) as created_by_username,
    MAX(m.created_at) as created_at
FROM manifests m
{$whereClause}
GROUP BY m.manifest_number
ORDER BY m.arrival_date DESC, MAX(m.created_at) DESC
LIMIT ? OFFSET ?";

$dataParams = array_merge($params, [$limit, $offset]);
$dataStmt = $conn->prepare($dataQuery);

$types = str_repeat('s', count($params)) . 'ii';
$dataStmt->bind_param($types, ...$dataParams);
$dataStmt->execute();
$result = $dataStmt->get_result();

$manifests = [];
while ($row = $result->fetch_assoc()) {
    $manifests[] = $row;
}

$dataStmt->close();

echo json_encode([
    'success' => true,
    'manifests' => $manifests,
    'pagination' => [
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => ceil($total / $limit)
    ]
]);
?>
