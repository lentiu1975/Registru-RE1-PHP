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

    // Fallback final la imagine generală
    if (!$containerImage) {
        $containerImage = '/images/containere/Containere.png';
    }

    $result['container_image'] = $containerImage;

    // Ship information - datele vin direct din baza de date (ship_name, ship_flag)
    // ship_name și ship_flag sunt deja în $result din query

    // Ship image path (dacă există ship_name)
    $shipName = $result['ship_name'] ?? null;
    $shipFlag = $result['ship_flag'] ?? null;

    if ($shipName && $shipName !== 'N/A') {
        // Transformă numele navei în format filename (lowercase, underscore)
        $shipImageName = strtolower(str_replace(' ', '_', $shipName));
        $result['ship_image'] = "/images/nave/{$shipImageName}.jpg";
    } else {
        $result['ship_image'] = null;
    }

    // Flag icon path (dacă există ship_flag)
    if ($shipFlag && $shipFlag !== 'N/A') {
        $flagCode = strtolower($shipFlag);
        $result['flag_image'] = "/images/steaguri/{$flagCode}.png";
    } else {
        $result['flag_image'] = null;
    }
}

jsonResponse([
    'query' => $query,
    'count' => count($results),
    'results' => $results
]);
