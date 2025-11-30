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

// Parametri paginare și căutare
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 50;
$search = $_GET['search'] ?? '';
$offset = ($page - 1) * $perPage;

// Construiește WHERE clause pentru căutare
$whereClause = "";
$params = [];

if (!empty($search)) {
    $whereClause = "WHERE container_number LIKE ? OR goods_description LIKE ? OR ship_name LIKE ? OR observatii LIKE ?";
    $searchParam = "%$search%";
    $params = [$searchParam, $searchParam, $searchParam, $searchParam];
}

// Numără total înregistrări
$countQuery = "SELECT COUNT(*) as total FROM manifest_entries $whereClause";
$totalResult = dbFetchOne($countQuery, $params);
$total = $totalResult['total'];

// Încarcă intrările
$query = "SELECT
    id,
    numar_curent,
    numar_manifest,
    permit_number,
    position_number,
    operation_request,
    data_inregistrare,
    container_number,
    model_container,
    container_type,
    packages,
    weight,
    goods_description,
    operation_type,
    ship_name,
    ship_flag,
    summary_number,
    linie_maritima,
    observatii,
    created_at
FROM manifest_entries
$whereClause
ORDER BY numar_curent ASC
LIMIT ? OFFSET ?";

$entries = dbFetchAll($query, array_merge($params, [$perPage, $offset]));

echo json_encode([
    'success' => true,
    'entries' => $entries,
    'pagination' => [
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => ceil($total / $perPage)
    ]
]);
?>
