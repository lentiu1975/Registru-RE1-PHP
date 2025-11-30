<?php
/**
 * API pentru gestionare tipuri containere
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
            $type = dbFetchOne("SELECT * FROM container_types WHERE id = ?", [$id]);
            jsonResponse($type);
        } else {
            $types = dbFetchAll("SELECT ct.*, COUNT(me.id) as entries_count
                                FROM container_types ct
                                LEFT JOIN manifest_entries me ON ct.id = me.container_type_id
                                GROUP BY ct.id
                                ORDER BY ct.model_code");
            jsonResponse(['data' => $types]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['model_code'])) {
            jsonResponse(['error' => 'Model code este obligatoriu'], 400);
        }

        dbQuery("INSERT INTO container_types (model_code, type_code, prefix, description, image) VALUES (?, ?, ?, ?, ?)", [
            $data['model_code'],
            $data['type_code'] ?? '',
            $data['prefix'] ?? '',
            $data['description'] ?? null,
            $data['image'] ?? null
        ]);

        $conn = getDbConnection();
        $id = $conn->insert_id;
        $conn->close();

        $type = dbFetchOne("SELECT * FROM container_types WHERE id = ?", [$id]);
        jsonResponse($type, 201);
        break;

    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['id'])) {
            jsonResponse(['error' => 'ID lipsă'], 400);
        }

        $updates = [];
        $params = [];

        foreach (['model_code', 'type_code', 'prefix', 'description', 'image'] as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                $params[] = $data[$field];
            }
        }

        if (!empty($updates)) {
            $params[] = $data['id'];
            dbQuery("UPDATE container_types SET " . implode(', ', $updates) . " WHERE id = ?", $params);
        }

        $type = dbFetchOne("SELECT * FROM container_types WHERE id = ?", [$data['id']]);
        jsonResponse($type);
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;

        if (!$id) {
            jsonResponse(['error' => 'ID lipsă'], 400);
        }

        dbQuery("DELETE FROM container_types WHERE id = ?", [$id]);
        jsonResponse(['success' => true]);
        break;

    default:
        jsonResponse(['error' => 'Metodă nepermisă'], 405);
}
