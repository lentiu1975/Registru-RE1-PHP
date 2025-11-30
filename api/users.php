<?php
/**
 * API pentru gestionare utilizatori
 * Suportă: GET, POST, PUT, DELETE
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

$method = $_SERVER['REQUEST_METHOD'];

// Verifică autentificare și permisiuni admin
if (!isset($_SESSION['user_id'])) {
    jsonResponse(['error' => 'Autentificare necesară'], 401);
}

// Verifică dacă utilizatorul este admin
$currentUser = dbFetchOne("SELECT is_admin FROM users WHERE id = ?", [$_SESSION['user_id']]);
if (!$currentUser || !$currentUser['is_admin']) {
    jsonResponse(['error' => 'Nu ai permisiuni de administrator'], 403);
}

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

/**
 * GET - Listare utilizatori sau detalii utilizator specific
 */
function handleGet() {
    $userId = $_GET['id'] ?? null;

    if ($userId) {
        // Returnează utilizator specific (fără parolă)
        $user = dbFetchOne(
            "SELECT id, username, email, full_name, company_name, is_active, is_admin,
                    created_at, last_login, updated_at
             FROM users WHERE id = ?",
            [$userId]
        );

        if (!$user) {
            jsonResponse(['error' => 'Utilizator negăsit'], 404);
        }

        jsonResponse($user);
    } else {
        // Returnează lista de utilizatori cu paginare
        $page = max(1, intval($_GET['page'] ?? 1));
        $perPage = min(100, max(10, intval($_GET['per_page'] ?? 20)));
        $search = $_GET['search'] ?? '';

        $users = getUsers($page, $perPage, $search);
        jsonResponse($users);
    }
}

/**
 * POST - Creare utilizator nou
 */
function handlePost() {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        jsonResponse(['error' => 'Date invalide'], 400);
    }

    // Validare
    if (empty($data['username'])) {
        jsonResponse(['error' => 'Username-ul este obligatoriu'], 400);
    }

    if (empty($data['password'])) {
        jsonResponse(['error' => 'Parola este obligatorie'], 400);
    }

    // Verifică dacă username-ul există deja
    $existing = dbFetchOne("SELECT id FROM users WHERE username = ?", [$data['username']]);
    if ($existing) {
        jsonResponse(['error' => 'Username-ul există deja'], 409);
    }

    // Verifică email dacă e furnizat
    if (!empty($data['email'])) {
        $existingEmail = dbFetchOne("SELECT id FROM users WHERE email = ?", [$data['email']]);
        if ($existingEmail) {
            jsonResponse(['error' => 'Email-ul există deja'], 409);
        }
    }

    // Hash parolă
    $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);

    // Inserează utilizator
    $sql = "INSERT INTO users (username, password, email, full_name, company_name, is_active, is_admin)
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    $params = [
        $data['username'],
        $passwordHash,
        $data['email'] ?? null,
        $data['full_name'] ?? null,
        $data['company_name'] ?? null,
        isset($data['is_active']) ? intval($data['is_active']) : 1,
        isset($data['is_admin']) ? intval($data['is_admin']) : 0
    ];

    dbQuery($sql, $params);

    $conn = getDbConnection();
    $userId = $conn->insert_id;
    $conn->close();

    // Returnează utilizatorul creat (fără parolă)
    $user = dbFetchOne(
        "SELECT id, username, email, full_name, company_name, is_active, is_admin, created_at
         FROM users WHERE id = ?",
        [$userId]
    );

    jsonResponse($user, 201);
}

/**
 * PUT - Actualizare utilizator
 */
function handlePut() {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || empty($data['id'])) {
        jsonResponse(['error' => 'Date invalide'], 400);
    }

    $userId = $data['id'];

    // Verifică dacă utilizatorul există
    $existing = dbFetchOne("SELECT id FROM users WHERE id = ?", [$userId]);
    if (!$existing) {
        jsonResponse(['error' => 'Utilizator negăsit'], 404);
    }

    // Nu permite ca un admin să se dezactiveze pe sine
    if ($userId == $_SESSION['user_id'] && isset($data['is_active']) && !$data['is_active']) {
        jsonResponse(['error' => 'Nu te poți dezactiva pe tine însuți'], 400);
    }

    // Nu permite ca singura admin să fie demovat
    if ($userId == $_SESSION['user_id'] && isset($data['is_admin']) && !$data['is_admin']) {
        $adminCount = dbFetchOne("SELECT COUNT(*) as count FROM users WHERE is_admin = 1");
        if ($adminCount['count'] <= 1) {
            jsonResponse(['error' => 'Nu poți șterge ultimul administrator'], 400);
        }
    }

    // Construiește query update dinamic
    $updates = [];
    $params = [];

    if (isset($data['username'])) {
        // Verifică dacă username-ul este luat de altcineva
        $existingUsername = dbFetchOne(
            "SELECT id FROM users WHERE username = ? AND id != ?",
            [$data['username'], $userId]
        );
        if ($existingUsername) {
            jsonResponse(['error' => 'Username-ul este deja folosit'], 409);
        }
        $updates[] = "username = ?";
        $params[] = $data['username'];
    }

    if (isset($data['email'])) {
        // Verifică dacă email-ul este luat de altcineva
        if (!empty($data['email'])) {
            $existingEmail = dbFetchOne(
                "SELECT id FROM users WHERE email = ? AND id != ?",
                [$data['email'], $userId]
            );
            if ($existingEmail) {
                jsonResponse(['error' => 'Email-ul este deja folosit'], 409);
            }
        }
        $updates[] = "email = ?";
        $params[] = $data['email'];
    }

    if (isset($data['full_name'])) {
        $updates[] = "full_name = ?";
        $params[] = $data['full_name'];
    }

    if (isset($data['company_name'])) {
        $updates[] = "company_name = ?";
        $params[] = $data['company_name'];
    }

    if (isset($data['is_active'])) {
        $updates[] = "is_active = ?";
        $params[] = intval($data['is_active']);
    }

    if (isset($data['is_admin'])) {
        $updates[] = "is_admin = ?";
        $params[] = intval($data['is_admin']);
    }

    // Actualizare parolă dacă e furnizată
    if (!empty($data['password'])) {
        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
        $updates[] = "password = ?";
        $params[] = $passwordHash;
    }

    if (empty($updates)) {
        jsonResponse(['error' => 'Nicio modificare specificată'], 400);
    }

    $params[] = $userId;
    $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
    dbQuery($sql, $params);

    // Returnează utilizatorul actualizat
    $user = dbFetchOne(
        "SELECT id, username, email, full_name, company_name, is_active, is_admin,
                created_at, last_login, updated_at
         FROM users WHERE id = ?",
        [$userId]
    );

    jsonResponse($user);
}

/**
 * DELETE - Ștergere utilizator
 */
function handleDelete() {
    $userId = $_GET['id'] ?? null;

    if (!$userId) {
        jsonResponse(['error' => 'ID utilizator lipsă'], 400);
    }

    // Nu permite ștergerea propriului cont
    if ($userId == $_SESSION['user_id']) {
        jsonResponse(['error' => 'Nu te poți șterge pe tine însuți'], 400);
    }

    // Verifică dacă utilizatorul există
    $existing = dbFetchOne("SELECT is_admin FROM users WHERE id = ?", [$userId]);
    if (!$existing) {
        jsonResponse(['error' => 'Utilizator negăsit'], 404);
    }

    // Nu permite ștergerea ultimului admin
    if ($existing['is_admin']) {
        $adminCount = dbFetchOne("SELECT COUNT(*) as count FROM users WHERE is_admin = 1");
        if ($adminCount['count'] <= 1) {
            jsonResponse(['error' => 'Nu poți șterge ultimul administrator'], 400);
        }
    }

    // Șterge utilizatorul
    dbQuery("DELETE FROM users WHERE id = ?", [$userId]);

    jsonResponse(['success' => true, 'message' => 'Utilizator șters cu succes']);
}

/**
 * Helper: Obține lista de utilizatori cu paginare
 */
function getUsers($page, $perPage, $search) {
    // Count total
    $countSql = "SELECT COUNT(*) as total FROM users WHERE 1=1";
    $params = [];

    if ($search) {
        $countSql .= " AND (username LIKE ? OR email LIKE ? OR full_name LIKE ? OR company_name LIKE ?)";
        $searchParam = "%{$search}%";
        $params = [$searchParam, $searchParam, $searchParam, $searchParam];
    }

    $countResult = dbFetchOne($countSql, $params);
    $total = $countResult['total'];

    // Calculează paginare
    $pagination = paginate($total, $perPage, $page);

    // Query pentru utilizatori (fără parole)
    $sql = "SELECT id, username, email, full_name, company_name, is_active, is_admin,
                   created_at, last_login, updated_at
            FROM users
            WHERE 1=1";

    if ($search) {
        $sql .= " AND (username LIKE ? OR email LIKE ? OR full_name LIKE ? OR company_name LIKE ?)";
    }

    $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";

    $params[] = $perPage;
    $params[] = $pagination['offset'];

    $users = dbFetchAll($sql, $params);

    return [
        'data' => $users,
        'pagination' => $pagination
    ];
}
