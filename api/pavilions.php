<?php
/**
 * API pentru gestionare pavilioane
 */

// Disable error display in output (would break JSON)
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    jsonResponse(['error' => 'Autentificare necesară'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $id = $_GET['id'] ?? null;

        if ($id) {
            $pavilion = dbFetchOne("SELECT * FROM pavilions WHERE id = ?", [$id]);
            jsonResponse($pavilion);
        } else {
            // Parametri pentru filtrare și căutare
            $hasImage = $_GET['has_image'] ?? '';
            $search = $_GET['search'] ?? '';

            // Query cu numarare nave din manifest_entries pe baza ship_flag
            $sql = "SELECT p.*,
                    (SELECT COUNT(DISTINCT me.ship_name)
                     FROM manifest_entries me
                     WHERE me.ship_flag = p.name) as ships_count
                    FROM pavilions p
                    WHERE 1=1";

            $params = [];

            // Filtru după prezența imaginii steag
            if ($hasImage === 'with') {
                $sql .= " AND p.flag_image IS NOT NULL AND p.flag_image != ''";
            } elseif ($hasImage === 'without') {
                $sql .= " AND (p.flag_image IS NULL OR p.flag_image = '')";
            }

            // Căutare în name sau country_name
            if (!empty($search)) {
                $sql .= " AND (p.name LIKE ? OR p.country_name LIKE ?)";
                $searchTerm = "%{$search}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }

            $sql .= " ORDER BY p.name";

            $pavilions = dbFetchAll($sql, $params);
            jsonResponse(['data' => $pavilions]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['name'])) {
            jsonResponse(['error' => 'Codul pavilionului este obligatoriu'], 400);
        }

        // Use direct connection to get insert_id
        $conn = getDbConnection();
        $stmt = $conn->prepare("INSERT INTO pavilions (name, country_name, flag_image) VALUES (?, ?, ?)");
        $code = strtoupper(trim($data['name'])); // name = cod (ex: TR, PA)
        $countryName = $data['country_name'] ?? null; // country_name = nume tara (ex: Turcia)
        $flagImage = $data['flag_image'] ?? null;
        $stmt->bind_param('sss', $code, $countryName, $flagImage);
        $stmt->execute();
        $id = $conn->insert_id;
        $stmt->close();
        $conn->close();

        $pavilion = dbFetchOne("SELECT * FROM pavilions WHERE id = ?", [$id]);
        jsonResponse($pavilion ?: ['success' => true, 'id' => $id], 201);
        break;

    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['id'])) {
            jsonResponse(['error' => 'ID lipsă'], 400);
        }

        $updates = [];
        $params = [];

        if (isset($data['name'])) {
            $updates[] = "name = ?";
            $params[] = $data['name'];
        }
        if (isset($data['country_name'])) {
            $updates[] = "country_name = ?";
            $params[] = $data['country_name'];
        }
        if (isset($data['flag_image'])) {
            $updates[] = "flag_image = ?";
            $params[] = $data['flag_image'];
        }

        if (!empty($updates)) {
            $params[] = $data['id'];
            dbQuery("UPDATE pavilions SET " . implode(', ', $updates) . " WHERE id = ?", $params);
        }

        $pavilion = dbFetchOne("SELECT * FROM pavilions WHERE id = ?", [$data['id']]);
        jsonResponse($pavilion);
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;

        if (!$id) {
            jsonResponse(['error' => 'ID lipsă'], 400);
        }

        dbQuery("DELETE FROM pavilions WHERE id = ?", [$id]);
        jsonResponse(['success' => true]);
        break;

    default:
        jsonResponse(['error' => 'Metodă nepermisă'], 405);
}
