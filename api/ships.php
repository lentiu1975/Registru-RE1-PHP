<?php
/**
 * API pentru gestionare nave
 * Coloane: id, name, pavilion_id, image, created_at
 */

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
            $ship = dbFetchOne("
                SELECT s.*, p.name as pavilion_name, p.flag_image as pavilion_flag
                FROM ships s
                LEFT JOIN pavilions p ON s.pavilion_id = p.id
                WHERE s.id = ?
            ", [$id]);
            jsonResponse($ship);
        } else {
            // Parametri pentru filtrare și căutare
            $hasImage = $_GET['has_image'] ?? '';
            $search = $_GET['search'] ?? '';

            // Query cu numărare înregistrări din manifest_entries pe baza ship_name
            $sql = "SELECT s.*, p.name as pavilion_name, p.flag_image as pavilion_flag,
                    (SELECT COUNT(DISTINCT me.id)
                     FROM manifest_entries me
                     WHERE me.ship_name = s.name) as entries_count
                    FROM ships s
                    LEFT JOIN pavilions p ON s.pavilion_id = p.id
                    WHERE 1=1";

            $params = [];

            // Filtru după prezența imaginii
            if ($hasImage === 'with') {
                $sql .= " AND s.image IS NOT NULL AND s.image != ''";
            } elseif ($hasImage === 'without') {
                $sql .= " AND (s.image IS NULL OR s.image = '')";
            }

            // Căutare în name
            if (!empty($search)) {
                $sql .= " AND s.name LIKE ?";
                $searchTerm = "%{$search}%";
                $params[] = $searchTerm;
            }

            $sql .= " ORDER BY s.name";

            $ships = dbFetchAll($sql, $params);
            jsonResponse(['data' => $ships]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['name'])) {
            jsonResponse(['error' => 'Numele navei este obligatoriu'], 400);
        }

        dbQuery("INSERT INTO ships (name, pavilion_id, image) VALUES (?, ?, ?)", [
            $data['name'],
            $data['pavilion_id'] ?? null,
            $data['image'] ?? null
        ]);

        $conn = getDbConnection();
        $id = $conn->insert_id;
        $conn->close();

        $ship = dbFetchOne("SELECT * FROM ships WHERE id = ?", [$id]);
        jsonResponse($ship, 201);
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
        if (array_key_exists('pavilion_id', $data)) {
            $updates[] = "pavilion_id = ?";
            $params[] = $data['pavilion_id'] ?: null;
        }
        if (isset($data['image'])) {
            $updates[] = "image = ?";
            $params[] = $data['image'];
        }

        if (!empty($updates)) {
            $params[] = $data['id'];
            dbQuery("UPDATE ships SET " . implode(', ', $updates) . " WHERE id = ?", $params);
        }

        $ship = dbFetchOne("SELECT * FROM ships WHERE id = ?", [$data['id']]);
        jsonResponse($ship);
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;

        if (!$id) {
            jsonResponse(['error' => 'ID lipsă'], 400);
        }

        dbQuery("DELETE FROM ships WHERE id = ?", [$id]);
        jsonResponse(['success' => true]);
        break;

    default:
        jsonResponse(['error' => 'Metodă nepermisă'], 405);
}
