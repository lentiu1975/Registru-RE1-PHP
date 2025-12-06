<?php
/**
 * API pentru trimitere credențiale utilizator prin email
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Verifică autentificare și permisiuni admin
if (!isset($_SESSION['user_id'])) {
    jsonResponse(['error' => 'Autentificare necesară'], 401);
}

$currentUser = dbFetchOne("SELECT is_admin FROM users WHERE id = ?", [$_SESSION['user_id']]);
if (!$currentUser || !$currentUser['is_admin']) {
    jsonResponse(['error' => 'Nu ai permisiuni de administrator'], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Metodă nepermisă'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    jsonResponse(['error' => 'Date invalide'], 400);
}

$userId = $data['user_id'] ?? null;
$password = $data['password'] ?? null;
$emailType = $data['type'] ?? 'new_account'; // 'new_account' sau 'password_change'

if (!$userId) {
    jsonResponse(['error' => 'ID utilizator lipsă'], 400);
}

if (!$password) {
    jsonResponse(['error' => 'Parola lipsă'], 400);
}

// Obține datele utilizatorului
$user = dbFetchOne("SELECT id, username, email, full_name FROM users WHERE id = ?", [$userId]);

if (!$user) {
    jsonResponse(['error' => 'Utilizator negăsit'], 404);
}

if (empty($user['email'])) {
    jsonResponse(['error' => 'Utilizatorul nu are email configurat'], 400);
}

// Obține setările email
$emailSettings = dbFetchOne("SELECT * FROM email_settings WHERE id = 1 AND is_active = 1");

if (!$emailSettings) {
    jsonResponse(['error' => 'Setările email nu sunt configurate sau sunt inactive'], 400);
}

// Trimite email
$result = sendCredentialsEmail($user, $password, $emailSettings, $emailType);

if ($result['success']) {
    jsonResponse(['success' => true, 'message' => 'Email trimis cu succes la ' . $user['email']]);
} else {
    jsonResponse(['error' => 'Eroare la trimitere: ' . $result['error']], 500);
}

/**
 * Trimite email cu credențiale folosind SMTP
 */
function sendCredentialsEmail($user, $password, $settings, $emailType = 'new_account') {
    $to = $user['email'];
    $username = $user['username'];
    $fullName = $user['full_name'] ?: $user['username'];
    $adminEmail = $settings['from_email'] ?? 'admin@vamactasud.lentiu.ro';

    // Mesaj diferit în funcție de tip
    if ($emailType === 'password_change') {
        $subject = 'Parola contului a fost modificata - Registru Import RE1';
        $body = "
Bună ziua, {$fullName}!

Parola contului dumneavoastră pentru aplicația Registru Import RE1 a fost modificată.

Noile date de autentificare:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Username: {$username}
Parola nouă: {$password}
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Link acces: http://vamactasud.lentiu.ro/login.php

Dacă nu ați solicitat această modificare, contactați administratorul la: {$adminEmail}

Cu stimă,
Echipa Registru Import RE1
";
    } else {
        $subject = 'Credentiale cont - Registru Import RE1';
        $body = "
Bună ziua, {$fullName}!

Contul dumneavoastră pentru aplicația Registru Import RE1 a fost creat.

Date de autentificare:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Username: {$username}
Parola: {$password}
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Link acces: http://vamactasud.lentiu.ro/login.php

Cu stimă,
Echipa Registru Import RE1
";
    }

    // Folosește PHPMailer dacă există, altfel mail() nativ
    $phpmailerPath = __DIR__ . '/../vendor/autoload.php';

    if (file_exists($phpmailerPath)) {
        require_once $phpmailerPath;

        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);

            // Server settings
            $mail->isSMTP();
            $mail->Host = $settings['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $settings['smtp_username'];
            $mail->Password = $settings['smtp_password'];
            $mail->SMTPSecure = $settings['smtp_encryption'] === 'ssl' ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = intval($settings['smtp_port']);
            $mail->CharSet = 'UTF-8';

            // Recipients
            $mail->setFrom($settings['from_email'], $settings['from_name']);
            $mail->addAddress($to, $fullName);

            // Content
            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body = $body;

            $mail->send();
            return ['success' => true];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $mail->ErrorInfo];
        }
    } else {
        // Fallback la mail() nativ cu SMTP
        // Notă: mail() nativ nu suportă direct SMTP, dar încercăm
        $headers = [
            'From' => $settings['from_name'] . ' <' . $settings['from_email'] . '>',
            'Reply-To' => $settings['from_email'],
            'Content-Type' => 'text/plain; charset=UTF-8',
            'X-Mailer' => 'PHP/' . phpversion()
        ];

        $headerString = '';
        foreach ($headers as $key => $value) {
            $headerString .= "{$key}: {$value}\r\n";
        }

        if (mail($to, $subject, $body, $headerString)) {
            return ['success' => true];
        } else {
            return ['success' => false, 'error' => 'Funcția mail() a eșuat. Verificați configurarea serverului.'];
        }
    }
}
