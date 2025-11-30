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

$manifestNumber = $_GET['manifest_number'] ?? '';

if (empty($manifestNumber)) {
    echo json_encode(['success' => false, 'error' => 'Număr manifest lipsă']);
    exit;
}

$conn = getDbConnection();

// Încarcă info manifest
$manifestInfo = dbFetchOne(
    "SELECT
        m.manifest_number,
        m.arrival_date,
        m.created_at,
        (SELECT ship_name FROM manifest_entries WHERE permit_number = m.manifest_number LIMIT 1) as ship_name
     FROM manifests m
     WHERE m.manifest_number = ?",
    [$manifestNumber]
);

if (!$manifestInfo) {
    echo json_encode(['success' => false, 'error' => 'Manifest negăsit']);
    exit;
}

// Încarcă containere cu TOATE câmpurile
$containers = dbFetchAll(
    "SELECT
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
    WHERE permit_number = ?
    ORDER BY numar_curent ASC",
    [$manifestNumber]
);

// Detectează duplicate containers
$containerCounts = [];
foreach ($containers as $container) {
    $num = $container['container_number'];
    if (!isset($containerCounts[$num])) {
        $containerCounts[$num] = 0;
    }
    $containerCounts[$num]++;
}

// Adaugă flag is_duplicate pentru fiecare container
foreach ($containers as &$container) {
    $container['is_duplicate'] = $containerCounts[$container['container_number']] > 1;
}
unset($container);

// Calculează statistici
$stats = [
    'total_containers' => count($containers),
    'containers_with_goods' => 0,
    'containers_with_observations' => 0,
    'duplicate_containers' => 0
];

foreach ($containers as $container) {
    if (!empty($container['goods_description'])) {
        $stats['containers_with_goods']++;
    }
    if (!empty($container['observatii'])) {
        $stats['containers_with_observations']++;
    }
    if ($container['is_duplicate']) {
        $stats['duplicate_containers']++;
    }
}

echo json_encode([
    'success' => true,
    'manifest' => $manifestInfo,
    'containers' => $containers,
    'stats' => $stats
]);
?>
