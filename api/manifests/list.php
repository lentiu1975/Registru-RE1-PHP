<?php
// Disable error display in output (would break JSON)
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);

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

// Verifică dacă coloana database_year_id există
$hasYearColumn = false;
$checkCol = $conn->query("SHOW COLUMNS FROM manifests LIKE 'database_year_id'");
if ($checkCol && $checkCol->num_rows > 0) {
    $hasYearColumn = true;
}

// Parametri căutare și paginare
$search = $_GET['search'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$yearId = $_GET['year_id'] ?? '';
$year = $_GET['year'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = intval($_GET['limit'] ?? 50);
$offset = ($page - 1) * $limit;

// Dacă s-a trimis anul numeric, găsește ID-ul corespunzător
if (!empty($year) && empty($yearId)) {
    $yearStmt = $conn->prepare("SELECT id FROM database_years WHERE year = ?");
    $yearStmt->bind_param('i', $year);
    $yearStmt->execute();
    $yearResult = $yearStmt->get_result();
    if ($yearRow = $yearResult->fetch_assoc()) {
        $yearId = $yearRow['id'];
    }
    $yearStmt->close();
}

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

if (!empty($yearId) && $hasYearColumn) {
    $where[] = "m.database_year_id = ?";
    $params[] = $yearId;
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

// Verifică dacă coloana created_by există
$hasCreatedBy = false;
$checkCreatedBy = $conn->query("SHOW COLUMNS FROM manifests LIKE 'created_by'");
if ($checkCreatedBy && $checkCreatedBy->num_rows > 0) {
    $hasCreatedBy = true;
}

// Query optimizat - folosim subquery pentru a evita JOIN-uri mari
$createdBySelect = $hasCreatedBy
    ? "(SELECT username FROM users WHERE id = m.created_by LIMIT 1) as created_by_username,"
    : "NULL as created_by_username,";

$dataQuery = "SELECT
    m.manifest_number,
    m.arrival_date,
    (SELECT ship_name FROM manifest_entries WHERE permit_number = m.manifest_number LIMIT 1) as ship_name,
    (SELECT COUNT(*) FROM manifest_entries WHERE permit_number = m.manifest_number) as container_count,
    {$createdBySelect}
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
