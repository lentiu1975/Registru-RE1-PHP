<?php
/**
 * API pentru gestionare template-uri import
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
            $template = dbFetchOne("SELECT * FROM import_templates WHERE id = ?", [$id]);
            if ($template && $template['column_mapping']) {
                $template['column_mapping'] = json_decode($template['column_mapping'], true);
            }
            jsonResponse($template);
        } else {
            $templates = dbFetchAll("SELECT * FROM import_templates ORDER BY created_at DESC");
            foreach ($templates as &$template) {
                if ($template['column_mapping']) {
                    $template['column_mapping'] = json_decode($template['column_mapping'], true);
                }
            }
            jsonResponse(['data' => $templates]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['name'])) {
            jsonResponse(['error' => 'Numele template-ului este obligatoriu'], 400);
        }

        $columnMapping = isset($data['column_mapping']) ? json_encode($data['column_mapping']) : '{}';

        dbQuery("INSERT INTO import_templates (name, description, file_format, start_row, column_mapping) VALUES (?, ?, ?, ?, ?)", [
            $data['name'],
            $data['description'] ?? null,
            $data['file_format'] ?? 'xlsx',
            $data['start_row'] ?? 2,
            $columnMapping
        ]);

        $conn = getDbConnection();
        $id = $conn->insert_id;
        $conn->close();

        $template = dbFetchOne("SELECT * FROM import_templates WHERE id = ?", [$id]);
        if ($template && $template['column_mapping']) {
            $template['column_mapping'] = json_decode($template['column_mapping'], true);
        }
        jsonResponse($template, 201);
        break;

    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['id'])) {
            jsonResponse(['error' => 'ID lipsă'], 400);
        }

        $updates = [];
        $params = [];

        foreach (['name', 'description', 'file_format', 'start_row'] as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                $params[] = $data[$field];
            }
        }

        if (isset($data['column_mapping'])) {
            $updates[] = "column_mapping = ?";
            $params[] = json_encode($data['column_mapping']);
        }

        if (!empty($updates)) {
            $params[] = $data['id'];
            dbQuery("UPDATE import_templates SET " . implode(', ', $updates) . " WHERE id = ?", $params);
        }

        $template = dbFetchOne("SELECT * FROM import_templates WHERE id = ?", [$data['id']]);
        if ($template && $template['column_mapping']) {
            $template['column_mapping'] = json_decode($template['column_mapping'], true);
        }
        jsonResponse($template);
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;

        if (!$id) {
            jsonResponse(['error' => 'ID lipsă'], 400);
        }

        dbQuery("DELETE FROM import_templates WHERE id = ?", [$id]);
        jsonResponse(['success' => true]);
        break;

    default:
        jsonResponse(['error' => 'Metodă nepermisă'], 405);
}
