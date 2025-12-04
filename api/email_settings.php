<?php
/**
 * API pentru gestionare setări email SMTP
 * Suportă: GET, POST, PUT
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
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
    case 'PUT':
        handleSave();
        break;
    default:
        jsonResponse(['error' => 'Metodă nepermisă'], 405);
}

/**
 * GET - Obține setările email curente
 */
function handleGet() {
    $settings = dbFetchOne("SELECT * FROM email_settings WHERE id = 1");

    if (!$settings) {
        // Return default empty settings
        jsonResponse([
            'id' => 1,
            'smtp_host' => '',
            'smtp_port' => 465,
            'smtp_username' => '',
            'smtp_password' => '',
            'smtp_encryption' => 'ssl',
            'from_email' => '',
            'from_name' => 'Registru RE1',
            'is_active' => 0
        ]);
    }

    // Mascează parola parțial pentru afișare
    if (!empty($settings['smtp_password'])) {
        $settings['smtp_password_masked'] = str_repeat('*', strlen($settings['smtp_password']));
        $settings['has_password'] = true;
    } else {
        $settings['smtp_password_masked'] = '';
        $settings['has_password'] = false;
    }

    // Nu trimitem parola reală
    unset($settings['smtp_password']);

    jsonResponse($settings);
}

/**
 * POST/PUT - Salvează setările email
 */
function handleSave() {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        jsonResponse(['error' => 'Date invalide'], 400);
    }

    // Validări de bază
    if (empty($data['smtp_host'])) {
        jsonResponse(['error' => 'Serverul SMTP este obligatoriu'], 400);
    }

    if (empty($data['smtp_port'])) {
        jsonResponse(['error' => 'Portul SMTP este obligatoriu'], 400);
    }

    if (empty($data['smtp_username'])) {
        jsonResponse(['error' => 'Username-ul SMTP este obligatoriu'], 400);
    }

    if (empty($data['from_email'])) {
        jsonResponse(['error' => 'Email-ul expeditor este obligatoriu'], 400);
    }

    // Verifică dacă există deja setări
    $existing = dbFetchOne("SELECT id, smtp_password FROM email_settings WHERE id = 1");

    // Pregătește datele
    $smtpHost = trim($data['smtp_host']);
    $smtpPort = intval($data['smtp_port']);
    $smtpUsername = trim($data['smtp_username']);
    $smtpEncryption = in_array($data['smtp_encryption'] ?? 'ssl', ['ssl', 'tls', 'none']) ? $data['smtp_encryption'] : 'ssl';
    $fromEmail = trim($data['from_email']);
    $fromName = trim($data['from_name'] ?? 'Registru RE1');
    $isActive = isset($data['is_active']) ? intval($data['is_active']) : 1;

    // Parola - păstrăm vechea dacă nu se trimite una nouă
    if (!empty($data['smtp_password'])) {
        $smtpPassword = $data['smtp_password'];
    } elseif ($existing) {
        $smtpPassword = $existing['smtp_password'];
    } else {
        jsonResponse(['error' => 'Parola SMTP este obligatorie'], 400);
    }

    if ($existing) {
        // Update
        dbQuery(
            "UPDATE email_settings SET
                smtp_host = ?, smtp_port = ?, smtp_username = ?, smtp_password = ?,
                smtp_encryption = ?, from_email = ?, from_name = ?, is_active = ?,
                updated_at = NOW()
             WHERE id = 1",
            [$smtpHost, $smtpPort, $smtpUsername, $smtpPassword, $smtpEncryption, $fromEmail, $fromName, $isActive]
        );
    } else {
        // Insert
        dbQuery(
            "INSERT INTO email_settings
                (id, smtp_host, smtp_port, smtp_username, smtp_password, smtp_encryption, from_email, from_name, is_active, created_at, updated_at)
             VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
            [$smtpHost, $smtpPort, $smtpUsername, $smtpPassword, $smtpEncryption, $fromEmail, $fromName, $isActive]
        );
    }

    jsonResponse(['success' => true, 'message' => 'Setări salvate cu succes']);
}
