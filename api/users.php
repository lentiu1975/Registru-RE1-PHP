<?php
/**
 * API pentru gestionare utilizatori
 * Suportă: GET, POST, PUT, DELETE
 *
 * Sistem de roluri:
 * - super_admin: acces complet, poate modifica orice utilizator
 * - admin: acces la admin panel, poate modifica doar useri normali și parola proprie
 * - user: acces doar la frontend (căutare)
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

// Verifică autentificare
if (!isset($_SESSION['user_id'])) {
    jsonResponse(['error' => 'Autentificare necesară'], 401);
}

// Obține utilizatorul curent cu rol
$currentUser = dbFetchOne("SELECT id, username, is_admin, role FROM users WHERE id = ?", [$_SESSION['user_id']]);
if (!$currentUser) {
    jsonResponse(['error' => 'Utilizator invalid'], 401);
}

// Determină rolul efectiv (pentru compatibilitate cu sistemul vechi)
$currentRole = $currentUser['role'] ?? ($currentUser['is_admin'] ? 'admin' : 'user');
$_SESSION['user_role'] = $currentRole;

// Verifică dacă utilizatorul are acces la admin panel
if ($currentRole === 'user') {
    jsonResponse(['error' => 'Nu ai permisiuni de administrator'], 403);
}

// Helper functions pentru verificarea permisiunilor
function isSuperAdmin() {
    global $currentRole;
    return $currentRole === 'super_admin';
}

function canEditUserRole($targetRole) {
    global $currentRole;
    if ($currentRole === 'super_admin') return true;
    if ($currentRole === 'admin' && $targetRole === 'user') return true;
    return false;
}

function canChangePasswordFor($targetUserId, $targetRole) {
    global $currentRole, $currentUser;
    if ($currentRole === 'super_admin') return true;
    if ($currentUser['id'] == $targetUserId) return true;
    if ($currentRole === 'admin' && $targetRole === 'user') return true;
    return false;
}

function canDeleteUser($targetUserId, $targetRole) {
    global $currentRole, $currentUser;
    if ($currentUser['id'] == $targetUserId) return false;
    if ($currentRole === 'super_admin') return true;
    if ($currentRole === 'admin' && $targetRole === 'user') return true;
    return false;
}

function getAssignableRoles() {
    global $currentRole;
    if ($currentRole === 'super_admin') return ['user', 'admin', 'super_admin'];
    if ($currentRole === 'admin') return ['user'];
    return [];
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
    global $currentUser, $currentRole;
    $userId = $_GET['id'] ?? null;

    if ($userId) {
        // Returnează utilizator specific (fără parolă)
        $user = dbFetchOne(
            "SELECT u.id, u.username, u.email, u.full_name, u.company_name, u.company_id,
                    u.is_active, u.is_admin, u.role, u.created_at, u.last_login, u.updated_at,
                    c.name as company_display_name
             FROM users u
             LEFT JOIN companies c ON u.company_id = c.id
             WHERE u.id = ?",
            [$userId]
        );

        if (!$user) {
            jsonResponse(['error' => 'Utilizator negăsit'], 404);
        }

        // Determină rolul efectiv pentru utilizatorul target
        $targetRole = $user['role'] ?? ($user['is_admin'] ? 'admin' : 'user');
        $user['effective_role'] = $targetRole;

        // Adaugă permisiuni
        $user['can_edit'] = canEditUserRole($targetRole) || $currentUser['id'] == $user['id'];
        $user['can_change_password'] = canChangePasswordFor($user['id'], $targetRole);
        $user['can_delete'] = canDeleteUser($user['id'], $targetRole);

        jsonResponse($user);
    } else {
        // Returnează lista de utilizatori cu paginare
        $page = max(1, intval($_GET['page'] ?? 1));
        $perPage = min(100, max(10, intval($_GET['per_page'] ?? 20)));
        $search = $_GET['search'] ?? '';

        $users = getUsers($page, $perPage, $search);

        // Adaugă informații despre roluri disponibile
        $users['assignable_roles'] = getAssignableRoles();
        $users['current_user_id'] = $currentUser['id'];
        $users['current_user_role'] = $currentRole;

        jsonResponse($users);
    }
}

/**
 * POST - Creare utilizator nou
 */
function handlePost() {
    global $currentRole;

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

    if (empty($data['email'])) {
        jsonResponse(['error' => 'Email-ul este obligatoriu'], 400);
    }

    // Verifică rolul - doar super_admin poate crea admini sau super_admini
    $newRole = $data['role'] ?? 'user';
    $assignableRoles = getAssignableRoles();
    if (!in_array($newRole, $assignableRoles)) {
        jsonResponse(['error' => 'Nu ai permisiunea să creezi utilizatori cu acest rol'], 403);
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

    // Determină is_admin pe baza rolului
    $isAdmin = ($newRole === 'super_admin' || $newRole === 'admin') ? 1 : 0;

    // Inserează utilizator
    $sql = "INSERT INTO users (username, password, email, full_name, company_id, is_active, is_admin, role)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $companyId = !empty($data['company_id']) ? intval($data['company_id']) : null;

    $params = [
        $data['username'],
        $passwordHash,
        $data['email'] ?? null,
        $data['full_name'] ?? null,
        $companyId,
        isset($data['is_active']) ? intval($data['is_active']) : 1,
        $isAdmin,
        $newRole
    ];

    // Folosește conexiune directă pentru a obține insert_id
    $conn = getDbConnection();
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssssiis', ...$params);
    $stmt->execute();
    $userId = $conn->insert_id;
    $stmt->close();
    $conn->close();

    if (!$userId) {
        jsonResponse(['error' => 'Eroare la crearea utilizatorului'], 500);
    }

    // Returnează utilizatorul creat (fără parolă)
    $user = dbFetchOne(
        "SELECT id, username, email, full_name, company_name, is_active, is_admin, role, created_at
         FROM users WHERE id = ?",
        [$userId]
    );

    jsonResponse($user, 201);
}

/**
 * PUT - Actualizare utilizator
 */
function handlePut() {
    global $currentUser, $currentRole;

    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || empty($data['id'])) {
        jsonResponse(['error' => 'Date invalide'], 400);
    }

    $userId = $data['id'];

    // Verifică dacă utilizatorul există și obține rolul actual
    $existing = dbFetchOne("SELECT id, is_admin, role FROM users WHERE id = ?", [$userId]);
    if (!$existing) {
        jsonResponse(['error' => 'Utilizator negăsit'], 404);
    }

    $targetRole = $existing['role'] ?? ($existing['is_admin'] ? 'admin' : 'user');

    // Verifică permisiuni de editare
    if (!canEditUserRole($targetRole) && $currentUser['id'] != $userId) {
        jsonResponse(['error' => 'Nu ai permisiunea să modifici acest utilizator'], 403);
    }

    // Nu permite ca un admin să se dezactiveze pe sine
    if ($userId == $_SESSION['user_id'] && isset($data['is_active']) && !$data['is_active']) {
        jsonResponse(['error' => 'Nu te poți dezactiva pe tine însuți'], 400);
    }

    // Nu permite schimbarea propriului rol
    if ($userId == $_SESSION['user_id'] && isset($data['role']) && $data['role'] !== $currentRole) {
        jsonResponse(['error' => 'Nu îți poți schimba propriul rol'], 400);
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

    if (array_key_exists('company_id', $data)) {
        $updates[] = "company_id = ?";
        $params[] = !empty($data['company_id']) ? intval($data['company_id']) : null;
    }

    if (isset($data['is_active'])) {
        $updates[] = "is_active = ?";
        $params[] = intval($data['is_active']);
    }

    // Actualizare rol dacă e furnizat
    if (isset($data['role'])) {
        $newRole = $data['role'];
        $assignableRoles = getAssignableRoles();

        if (!in_array($newRole, $assignableRoles)) {
            jsonResponse(['error' => 'Nu ai permisiunea să atribui acest rol'], 403);
        }

        // Admin nu poate schimba rolul altor admini
        if ($currentRole === 'admin' && $targetRole !== 'user') {
            jsonResponse(['error' => 'Nu ai permisiunea să modifici rolul acestui utilizator'], 403);
        }

        $updates[] = "role = ?";
        $params[] = $newRole;

        // Actualizează și is_admin pentru compatibilitate
        $isAdmin = ($newRole === 'super_admin' || $newRole === 'admin') ? 1 : 0;
        $updates[] = "is_admin = ?";
        $params[] = $isAdmin;
    }

    // Actualizare parolă dacă e furnizată
    if (!empty($data['password'])) {
        if (!canChangePasswordFor($userId, $targetRole)) {
            jsonResponse(['error' => 'Nu ai permisiunea să schimbi parola acestui utilizator'], 403);
        }
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
        "SELECT id, username, email, full_name, company_name, is_active, is_admin, role,
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
    global $currentUser, $currentRole;

    $userId = $_GET['id'] ?? null;

    if (!$userId) {
        jsonResponse(['error' => 'ID utilizator lipsă'], 400);
    }

    // Nu permite ștergerea propriului cont
    if ($userId == $_SESSION['user_id']) {
        jsonResponse(['error' => 'Nu te poți șterge pe tine însuți'], 400);
    }

    // Verifică dacă utilizatorul există
    $existing = dbFetchOne("SELECT is_admin, role FROM users WHERE id = ?", [$userId]);
    if (!$existing) {
        jsonResponse(['error' => 'Utilizator negăsit'], 404);
    }

    $targetRole = $existing['role'] ?? ($existing['is_admin'] ? 'admin' : 'user');

    // Verifică permisiuni de ștergere
    if (!canDeleteUser($userId, $targetRole)) {
        jsonResponse(['error' => 'Nu ai permisiunea să ștergi acest utilizator'], 403);
    }

    // Nu permite ștergerea ultimului super_admin
    if ($targetRole === 'super_admin') {
        $superAdminCount = dbFetchOne("SELECT COUNT(*) as count FROM users WHERE role = 'super_admin'");
        if ($superAdminCount['count'] <= 1) {
            jsonResponse(['error' => 'Nu poți șterge ultimul super administrator'], 400);
        }
    }

    // Șterge utilizatorul
    dbQuery("DELETE FROM users WHERE id = ?", [$userId]);

    jsonResponse(['success' => true, 'message' => 'Utilizator șters cu succes']);
}

/**
 * Helper: Obține lista de utilizatori cu paginare și filtre
 */
function getUsers($page, $perPage, $search) {
    global $currentUser;

    // Filtre suplimentare
    $companyId = $_GET['company_id'] ?? null;
    $lastAccessFilter = $_GET['last_access'] ?? null;

    // Count total
    $countSql = "SELECT COUNT(*) as total FROM users u WHERE 1=1";
    $params = [];

    if ($search) {
        $countSql .= " AND (u.username LIKE ? OR u.email LIKE ? OR u.full_name LIKE ? OR u.company_name LIKE ?)";
        $searchParam = "%{$search}%";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
    }

    if ($companyId) {
        $countSql .= " AND u.company_id = ?";
        $params[] = $companyId;
    }

    // Filtru după ultima accesare
    if ($lastAccessFilter) {
        $dateCondition = getLastAccessCondition($lastAccessFilter);
        if ($dateCondition) {
            $countSql .= $dateCondition;
        }
    }

    $countResult = dbFetchOne($countSql, $params);
    $total = $countResult['total'];

    // Calculează paginare
    $pagination = paginate($total, $perPage, $page);

    // Query pentru utilizatori (fără parole)
    $sql = "SELECT u.id, u.username, u.email, u.full_name, u.company_name, u.company_id,
                   u.is_active, u.is_admin, u.role, u.created_at, u.last_login, u.updated_at,
                   c.name as company_display_name
            FROM users u
            LEFT JOIN companies c ON u.company_id = c.id
            WHERE 1=1";

    $queryParams = [];

    if ($search) {
        $sql .= " AND (u.username LIKE ? OR u.email LIKE ? OR u.full_name LIKE ? OR u.company_name LIKE ?)";
        $searchParam = "%{$search}%";
        $queryParams = array_merge($queryParams, [$searchParam, $searchParam, $searchParam, $searchParam]);
    }

    if ($companyId) {
        $sql .= " AND u.company_id = ?";
        $queryParams[] = $companyId;
    }

    if ($lastAccessFilter) {
        $dateCondition = getLastAccessCondition($lastAccessFilter);
        if ($dateCondition) {
            $sql .= $dateCondition;
        }
    }

    $sql .= " ORDER BY u.created_at DESC LIMIT ? OFFSET ?";

    $queryParams[] = $perPage;
    $queryParams[] = $pagination['offset'];

    $users = dbFetchAll($sql, $queryParams);

    // Adaugă permisiuni pentru fiecare utilizator
    foreach ($users as &$user) {
        $targetRole = $user['role'] ?? ($user['is_admin'] ? 'admin' : 'user');
        $user['effective_role'] = $targetRole;
        $user['can_edit'] = canEditUserRole($targetRole) || $currentUser['id'] == $user['id'];
        $user['can_change_password'] = canChangePasswordFor($user['id'], $targetRole);
        $user['can_delete'] = canDeleteUser($user['id'], $targetRole);
    }

    return [
        'data' => $users,
        'pagination' => $pagination
    ];
}

/**
 * Helper: Generează condiția SQL pentru filtrul de ultima accesare
 */
function getLastAccessCondition($filter) {
    switch ($filter) {
        case 'today':
            return " AND DATE(u.last_login) = CURDATE()";
        case 'yesterday':
            return " AND DATE(u.last_login) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
        case '2days':
            return " AND (u.last_login IS NULL OR u.last_login < DATE_SUB(NOW(), INTERVAL 2 DAY))";
        case '3days':
            return " AND (u.last_login IS NULL OR u.last_login < DATE_SUB(NOW(), INTERVAL 3 DAY))";
        case '1week':
            return " AND (u.last_login IS NULL OR u.last_login < DATE_SUB(NOW(), INTERVAL 1 WEEK))";
        case '1month':
            return " AND (u.last_login IS NULL OR u.last_login < DATE_SUB(NOW(), INTERVAL 1 MONTH))";
        case '3months':
            return " AND (u.last_login IS NULL OR u.last_login < DATE_SUB(NOW(), INTERVAL 3 MONTH))";
        case '6months':
            return " AND (u.last_login IS NULL OR u.last_login < DATE_SUB(NOW(), INTERVAL 6 MONTH))";
        case '1year':
            return " AND (u.last_login IS NULL OR u.last_login < DATE_SUB(NOW(), INTERVAL 1 YEAR))";
        case 'never':
            return " AND u.last_login IS NULL";
        default:
            return null;
    }
}
