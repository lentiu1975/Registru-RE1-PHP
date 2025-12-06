<?php
/**
 * API pentru gestionare companii
 * GET - listare companii
 * POST - adaugă companie
 * PUT - actualizează companie
 * DELETE - șterge companie
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

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

// Verifică permisiuni admin
$currentUser = dbFetchOne("SELECT is_admin, role FROM users WHERE id = ?", [$_SESSION['user_id']]);
if (!$currentUser || !$currentUser['is_admin']) {
    jsonResponse(['error' => 'Nu ai permisiuni de administrator'], 403);
}

$method = $_SERVER['REQUEST_METHOD'];

// Verifică dacă tabelul există, dacă nu - îl creează
ensureCompaniesTableExists();

switch ($method) {
    case 'GET':
        handleGet();
        break;
    case 'POST':
        handlePost();
        break;
    case 'PUT':
        handlePut();
        break;
    case 'DELETE':
        handleDelete();
        break;
    default:
        jsonResponse(['error' => 'Metodă nepermisă'], 405);
}

function ensureCompaniesTableExists() {
    $conn = getDbConnection();

    // Verifică dacă tabelul există
    $result = $conn->query("SHOW TABLES LIKE 'companies'");
    if ($result->num_rows === 0) {
        // Creează tabelul
        $conn->query("
            CREATE TABLE companies (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL UNIQUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Migrează datele existente din users.company_name
        $conn->query("
            INSERT IGNORE INTO companies (name)
            SELECT DISTINCT company_name
            FROM users
            WHERE company_name IS NOT NULL AND company_name != ''
        ");
    }

    // Verifică dacă coloana company_id există în users
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'company_id'");
    if ($result->num_rows === 0) {
        $conn->query("ALTER TABLE users ADD COLUMN company_id INT NULL AFTER company_name");

        // Actualizează company_id pe baza company_name existent
        $conn->query("
            UPDATE users u
            SET u.company_id = (
                SELECT c.id FROM companies c WHERE c.name = u.company_name LIMIT 1
            )
            WHERE u.company_name IS NOT NULL AND u.company_name != ''
        ");
    }

    $conn->close();
}

function handleGet() {
    $search = $_GET['search'] ?? '';

    $sql = "SELECT c.*,
            (SELECT COUNT(*) FROM users WHERE company_id = c.id) as user_count
            FROM companies c WHERE 1=1";
    $params = [];

    if ($search) {
        $sql .= " AND c.name LIKE ?";
        $params[] = "%{$search}%";
    }

    $sql .= " ORDER BY c.name ASC";

    $companies = dbFetchAll($sql, $params);
    jsonResponse(['data' => $companies]);
}

function handlePost() {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || empty($data['name'])) {
        jsonResponse(['error' => 'Numele companiei este obligatoriu'], 400);
    }

    $name = trim($data['name']);

    // Verifică dacă există deja
    $existing = dbFetchOne("SELECT id FROM companies WHERE name = ?", [$name]);
    if ($existing) {
        jsonResponse(['error' => 'Compania există deja'], 409);
    }

    $conn = getDbConnection();
    $stmt = $conn->prepare("INSERT INTO companies (name) VALUES (?)");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $id = $conn->insert_id;
    $stmt->close();
    $conn->close();

    $company = dbFetchOne("SELECT * FROM companies WHERE id = ?", [$id]);
    jsonResponse($company, 201);
}

function handlePut() {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || empty($data['id']) || empty($data['name'])) {
        jsonResponse(['error' => 'Date invalide'], 400);
    }

    $id = $data['id'];
    $name = trim($data['name']);

    // Verifică dacă există
    $existing = dbFetchOne("SELECT id FROM companies WHERE id = ?", [$id]);
    if (!$existing) {
        jsonResponse(['error' => 'Compania nu există'], 404);
    }

    // Verifică dacă numele e luat de altcineva
    $duplicate = dbFetchOne("SELECT id FROM companies WHERE name = ? AND id != ?", [$name, $id]);
    if ($duplicate) {
        jsonResponse(['error' => 'O altă companie cu acest nume există deja'], 409);
    }

    dbQuery("UPDATE companies SET name = ? WHERE id = ?", [$name, $id]);

    $company = dbFetchOne("SELECT * FROM companies WHERE id = ?", [$id]);
    jsonResponse($company);
}

function handleDelete() {
    $id = $_GET['id'] ?? null;

    if (!$id) {
        jsonResponse(['error' => 'ID lipsă'], 400);
    }

    // Verifică dacă există
    $existing = dbFetchOne("SELECT id FROM companies WHERE id = ?", [$id]);
    if (!$existing) {
        jsonResponse(['error' => 'Compania nu există'], 404);
    }

    // Verifică dacă are utilizatori asociați
    $userCount = dbFetchOne("SELECT COUNT(*) as cnt FROM users WHERE company_id = ?", [$id]);
    if ($userCount['cnt'] > 0) {
        jsonResponse(['error' => 'Nu poți șterge compania - are ' . $userCount['cnt'] . ' utilizatori asociați'], 400);
    }

    dbQuery("DELETE FROM companies WHERE id = ?", [$id]);
    jsonResponse(['success' => true, 'message' => 'Companie ștearsă']);
}
