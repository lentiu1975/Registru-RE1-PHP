<?php
/**
 * API pentru căutare containere
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Verifică autentificare
if (!isset($_SESSION['user_id'])) {
    jsonResponse(['error' => 'Autentificare necesară'], 401);
}

$query = trim($_GET['q'] ?? '');
$yearId = $_GET['year_id'] ?? null;

if (empty($query)) {
    jsonResponse(['error' => 'Query de căutare lipsă'], 400);
}

// Validare minim 7 cifre
$digitCount = preg_match_all('/\d/', $query);
if ($digitCount < 7) {
    jsonResponse(['error' => 'Introduceți minim 7 cifre pentru căutare'], 400);
}

// Dacă nu e specificat anul, folosește anul activ
if (!$yearId) {
    $activeYear = getActiveYear();
    $yearId = $activeYear ? $activeYear['id'] : null;
}

// Căutare cu filtrare după anul selectat
$sql = "SELECT me.*, m.manifest_number, m.arrival_date
        FROM manifest_entries me
        LEFT JOIN manifests m ON me.manifest_id = m.id
        WHERE (me.container_number LIKE ?
           OR COALESCE(me.goods_description, '') LIKE ?
           OR COALESCE(m.manifest_number, '') LIKE ?)";

$searchParam = "%{$query}%";
$params = [$searchParam, $searchParam, $searchParam];

// Adaugă filtru pentru anul selectat (dacă există)
if ($yearId) {
    $sql .= " AND (me.database_year_id = ? OR me.database_year_id IS NULL)";
    $params[] = $yearId;
}

$sql .= " ORDER BY CAST(me.position_number AS UNSIGNED) ASC, me.id ASC LIMIT 100";

$results = dbFetchAll($sql, $params);

// Procesează fiecare rezultat
foreach ($results as &$result) {
    // Container image bazat pe prefixul din container_number
    $containerNumber = $result['container_number'] ?? '';
    $containerType = $result['container_type'] ?? '45G1';
    $prefix = substr($containerNumber, 0, 4); // Ex: CAAU din CAAU8601408

    // Caută imagine container în /images/containere/{type}/{prefix}.{ext}
    $containerImage = null;
    $extensions = ['jpg', 'png', 'jpeg'];

    foreach ($extensions as $ext) {
        $imagePath = "/images/containere/{$containerType}/{$prefix}.{$ext}";
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . $imagePath)) {
            $containerImage = $imagePath;
            break;
        }
    }

    // Fallback la imagine generică tip container
    if (!$containerImage) {
        foreach ($extensions as $ext) {
            $genericPath = "/images/containere/{$containerType}.{$ext}";
            if (file_exists($_SERVER['DOCUMENT_ROOT'] . $genericPath)) {
                $containerImage = $genericPath;
                break;
            }
        }
    }

    // Fallback final la imagine model
    if (!$containerImage) {
        $containerImage = '/assets/images/container_model.png';
    }

    $result['container_image'] = $containerImage;

    // Ship information - caută nava în tabela ships pentru a obține imaginea și pavilionul
    $shipName = $result['ship_name'] ?? null;

    if ($shipName && $shipName !== 'N/A') {
        // Caută nava în tabela ships cu pavilionul asociat
        $shipData = dbFetchOne("
            SELECT s.image as ship_image, p.flag_image, p.name as pavilion_name
            FROM ships s
            LEFT JOIN pavilions p ON s.pavilion_id = p.id
            WHERE s.name = ?
        ", [$shipName]);

        if ($shipData) {
            $result['ship_image'] = $shipData['ship_image'] ?: '/assets/images/vapor_model.png';
            $result['flag_image'] = $shipData['flag_image'] ?: null;
            $result['pavilion_name'] = $shipData['pavilion_name'] ?: null;
        } else {
            // Fallback - nava nu e în tabela ships
            $result['ship_image'] = '/assets/images/vapor_model.png';
            $result['flag_image'] = null;
            $result['pavilion_name'] = null;
        }
    } else {
        $result['ship_image'] = null;
        $result['flag_image'] = null;
        $result['pavilion_name'] = null;
    }
}

jsonResponse([
    'query' => $query,
    'count' => count($results),
    'results' => $results
]);
