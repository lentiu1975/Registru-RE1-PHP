<?php
/**
 * API pentru gestionare tipuri containere
 * Coloane: model_container (MRSU45G1), tip_container (45G1), imagine, descriere
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
            // Parametri pentru filtrare și căutare
            $tipFilter = $_GET['tip_container'] ?? '';
            $search = $_GET['search'] ?? '';
            $hasImage = $_GET['has_image'] ?? '';

            // Query cu numărare înregistrări din manifest_entries
            // model_container din container_types trebuie să se potrivească cu model_container din manifest_entries
            $sql = "SELECT ct.*,
                    (SELECT COUNT(*) FROM manifest_entries me
                     WHERE me.model_container = ct.model_container) as entries_count
                    FROM container_types ct
                    WHERE 1=1";

            $params = [];

            // Filtru după tip container
            if (!empty($tipFilter)) {
                $sql .= " AND ct.tip_container = ?";
                $params[] = $tipFilter;
            }

            // Filtru după prezența imaginii
            if ($hasImage === 'with') {
                $sql .= " AND ct.imagine IS NOT NULL AND ct.imagine != ''";
            } elseif ($hasImage === 'without') {
                $sql .= " AND (ct.imagine IS NULL OR ct.imagine = '')";
            }

            // Căutare în model_container sau descriere
            if (!empty($search)) {
                $sql .= " AND (ct.model_container LIKE ? OR ct.descriere LIKE ?)";
                $searchTerm = "%{$search}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }

            $sql .= " ORDER BY ct.tip_container, ct.model_container";

            $types = dbFetchAll($sql, $params);

            // Obține lista de tipuri unice pentru dropdown filtru
            $tipuriUnice = dbFetchAll("SELECT DISTINCT tip_container FROM container_types ORDER BY tip_container");

            jsonResponse([
                'data' => $types,
                'tipuri' => array_column($tipuriUnice, 'tip_container')
            ]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);

        $model = $data['model_container'] ?? '';
        $tip = $data['tip_container'] ?? '';

        if (empty($model)) {
            jsonResponse(['error' => 'Model container este obligatoriu'], 400);
        }

        dbQuery("INSERT INTO container_types (model_container, tip_container, imagine, descriere) VALUES (?, ?, ?, ?)", [
            $model,
            $tip,
            $data['imagine'] ?? null,
            $data['descriere'] ?? null
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

        // Coloane: model_container, tip_container, imagine, descriere
        foreach (['model_container', 'tip_container', 'imagine', 'descriere'] as $field) {
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
