<?php
/**
 * API pentru gestionare template-uri import
 * Structura tabelei: id, name, description, column_mappings (JSON), is_default, created_at, updated_at
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
            if ($template) {
                // Parsează JSON-ul column_mappings
                if (isset($template['column_mappings'])) {
                    $template['column_mapping'] = json_decode($template['column_mappings'], true) ?: [];
                }
                // Setează valori default
                $template['start_row'] = $template['start_row'] ?? 2;
                $template['file_format'] = $template['file_format'] ?? 'xlsx';
            }
            jsonResponse($template);
        } else {
            $templates = dbFetchAll("SELECT * FROM import_templates ORDER BY created_at DESC");
            foreach ($templates as &$template) {
                if (isset($template['column_mappings'])) {
                    $template['column_mapping'] = json_decode($template['column_mappings'], true) ?: [];
                }
                $template['start_row'] = $template['start_row'] ?? 2;
                $template['file_format'] = $template['file_format'] ?? 'xlsx';
            }
            jsonResponse(['data' => $templates ?: []]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['name'])) {
            jsonResponse(['error' => 'Numele template-ului este obligatoriu'], 400);
        }

        $columnMappings = isset($data['column_mapping']) ? json_encode($data['column_mapping']) : '{}';
        $startRow = intval($data['start_row'] ?? 2);

        dbQuery("INSERT INTO import_templates (name, description, column_mappings, start_row, is_default) VALUES (?, ?, ?, ?, ?)", [
            $data['name'],
            $data['description'] ?? null,
            $columnMappings,
            $startRow,
            $data['is_default'] ?? 0
        ]);

        $conn = getDbConnection();
        $id = $conn->insert_id;
        $conn->close();

        $template = dbFetchOne("SELECT * FROM import_templates WHERE id = ?", [$id]);
        if ($template && isset($template['column_mappings'])) {
            $template['column_mapping'] = json_decode($template['column_mappings'], true) ?: [];
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

        if (isset($data['name'])) {
            $updates[] = "name = ?";
            $params[] = $data['name'];
        }
        if (isset($data['description'])) {
            $updates[] = "description = ?";
            $params[] = $data['description'];
        }
        if (isset($data['is_default'])) {
            $updates[] = "is_default = ?";
            $params[] = $data['is_default'];
        }
        if (isset($data['column_mapping'])) {
            $updates[] = "column_mappings = ?";
            $params[] = json_encode($data['column_mapping']);
        }
        if (isset($data['start_row'])) {
            $updates[] = "start_row = ?";
            $params[] = intval($data['start_row']);
        }

        if (!empty($updates)) {
            $updates[] = "updated_at = NOW()";
            $params[] = $data['id'];
            dbQuery("UPDATE import_templates SET " . implode(', ', $updates) . " WHERE id = ?", $params);
        }

        $template = dbFetchOne("SELECT * FROM import_templates WHERE id = ?", [$data['id']]);
        if ($template && isset($template['column_mappings'])) {
            $template['column_mapping'] = json_decode($template['column_mappings'], true) ?: [];
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
