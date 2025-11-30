<?php
/**
 * API pentru gestionare ani baze de date
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
        $years = dbFetchAll("SELECT * FROM database_years ORDER BY year DESC");
        jsonResponse(['data' => $years]);
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['year'])) {
            jsonResponse(['error' => 'Anul este obligatoriu'], 400);
        }

        // Verifică dacă anul există deja
        $existing = dbFetchOne("SELECT id FROM database_years WHERE year = ?", [$data['year']]);
        if ($existing) {
            jsonResponse(['error' => 'Anul există deja'], 409);
        }

        dbQuery("INSERT INTO database_years (year, is_active) VALUES (?, 0)", [$data['year']]);

        $conn = getDbConnection();
        $id = $conn->insert_id;
        $conn->close();

        $year = dbFetchOne("SELECT * FROM database_years WHERE id = ?", [$id]);
        jsonResponse($year, 201);
        break;

    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['id'])) {
            jsonResponse(['error' => 'ID lipsă'], 400);
        }

        // Dacă se activează acest an, dezactivează toate celelalte
        if (!empty($data['is_active'])) {
            dbQuery("UPDATE database_years SET is_active = 0 WHERE 1=1");
        }

        dbQuery("UPDATE database_years SET is_active = ? WHERE id = ?", [
            intval($data['is_active'] ?? 0),
            $data['id']
        ]);

        $year = dbFetchOne("SELECT * FROM database_years WHERE id = ?", [$data['id']]);
        jsonResponse($year);
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;

        if (!$id) {
            jsonResponse(['error' => 'ID lipsă'], 400);
        }

        // Nu permite ștergerea anului activ
        $year = dbFetchOne("SELECT is_active FROM database_years WHERE id = ?", [$id]);
        if ($year && $year['is_active']) {
            jsonResponse(['error' => 'Nu poți șterge anul activ'], 400);
        }

        dbQuery("DELETE FROM database_years WHERE id = ?", [$id]);
        jsonResponse(['success' => true]);
        break;

    default:
        jsonResponse(['error' => 'Metodă nepermisă'], 405);
}
