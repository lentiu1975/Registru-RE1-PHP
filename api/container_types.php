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

            // Parametri paginare
            $page = max(1, intval($_GET['page'] ?? 1));
            $perPage = max(10, min(200, intval($_GET['per_page'] ?? 50)));
            $offset = ($page - 1) * $perPage;

            // Construiește WHERE clause
            $whereClause = "WHERE 1=1";
            $params = [];

            // Filtru după tip container
            if (!empty($tipFilter)) {
                $whereClause .= " AND ct.tip_container = ?";
                $params[] = $tipFilter;
            }

            // Filtru după prezența imaginii
            if ($hasImage === 'with') {
                $whereClause .= " AND ct.imagine IS NOT NULL AND ct.imagine != ''";
            } elseif ($hasImage === 'without') {
                $whereClause .= " AND (ct.imagine IS NULL OR ct.imagine = '')";
            }

            // Căutare în model_container sau descriere
            if (!empty($search)) {
                $whereClause .= " AND (ct.model_container LIKE ? OR ct.descriere LIKE ?)";
                $searchTerm = "%{$search}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }

            // Numără total înregistrări
            $countSql = "SELECT COUNT(*) as total FROM container_types ct $whereClause";
            $totalResult = dbFetchOne($countSql, $params);
            $total = $totalResult['total'] ?? 0;

            // Query cu numărare înregistrări din manifest_entries și paginare
            $sql = "SELECT ct.*,
                    (SELECT COUNT(*) FROM manifest_entries me
                     WHERE me.model_container = ct.model_container) as entries_count
                    FROM container_types ct
                    $whereClause
                    ORDER BY ct.tip_container, ct.model_container
                    LIMIT ? OFFSET ?";

            $types = dbFetchAll($sql, array_merge($params, [$perPage, $offset]));

            // Obține lista de tipuri unice pentru dropdown filtru
            $tipuriUnice = dbFetchAll("SELECT DISTINCT tip_container FROM container_types ORDER BY tip_container");

            jsonResponse([
                'data' => $types,
                'tipuri' => array_column($tipuriUnice, 'tip_container'),
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'per_page' => $perPage,
                    'total_pages' => $total > 0 ? ceil($total / $perPage) : 1
                ]
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
