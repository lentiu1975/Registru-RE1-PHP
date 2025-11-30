<?php
/**
 * API pentru gestionare pavilioane
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
            $pavilion = dbFetchOne("SELECT * FROM pavilions WHERE id = ?", [$id]);
            jsonResponse($pavilion);
        } else {
            $pavilions = dbFetchAll("SELECT p.*, COUNT(s.id) as ships_count
                                     FROM pavilions p
                                     LEFT JOIN ships s ON p.id = s.pavilion_id
                                     GROUP BY p.id
                                     ORDER BY p.name");
            jsonResponse(['data' => $pavilions]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['name'])) {
            jsonResponse(['error' => 'Numele pavilionului este obligatoriu'], 400);
        }

        dbQuery("INSERT INTO pavilions (name, country_name, flag_image) VALUES (?, ?, ?)", [
            $data['name'],
            $data['country_name'] ?? null,
            $data['flag_image'] ?? null
        ]);

        $conn = getDbConnection();
        $id = $conn->insert_id;
        $conn->close();

        $pavilion = dbFetchOne("SELECT * FROM pavilions WHERE id = ?", [$id]);
        jsonResponse($pavilion, 201);
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
