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
        MAX(m.created_at) as created_at,
        MAX(me.nume_nava) as ship_name
     FROM manifests m
     LEFT JOIN manifest_entries me ON m.manifest_number = me.permit_number
     WHERE m.manifest_number = ?
     GROUP BY m.manifest_number",
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
        numar_permis,
        numar_pozitie,
        cerere_operatiune,
        data_inregistrare,
        container as container_number,
        model_container,
        tip_container,
        numar_colete,
        greutate_bruta,
        descriere_marfa as goods_description,
        seal_number,
        tip_operatiune,
        nume_nava as ship_name,
        pavilion_nava,
        numar_sumara,
        linie_maritima,
        observatii,
        created_at
    FROM manifest_entries
    WHERE permit_number = ?
    ORDER BY numar_curent ASC",
    [$manifestNumber]
);

// Calculează statistici
$stats = [
    'total_containers' => count($containers),
    'containers_with_seal' => 0,
    'containers_with_goods' => 0
];

foreach ($containers as $container) {
    if (!empty($container['seal_number'])) {
        $stats['containers_with_seal']++;
    }
    if (!empty($container['goods_description'])) {
        $stats['containers_with_goods']++;
    }
}

echo json_encode([
    'success' => true,
    'manifest' => $manifestInfo,
    'containers' => $containers,
    'stats' => $stats
]);
?>
