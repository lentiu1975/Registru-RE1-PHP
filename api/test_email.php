<?php
/**
 * API pentru testare conexiune SMTP
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

// Obține setările email
$settings = dbFetchOne("SELECT * FROM email_settings WHERE id = 1");

if (!$settings || empty($settings['smtp_host'])) {
    jsonResponse(['error' => 'Setările email nu sunt configurate. Salvează setările mai întâi.'], 400);
}

// Test conexiune SMTP
$result = testSmtpConnection($settings);

if ($result['success']) {
    jsonResponse([
        'success' => true,
        'message' => 'Conexiune SMTP reușită!',
        'details' => $result['details']
    ]);
} else {
    jsonResponse([
        'success' => false,
        'error' => $result['error'],
        'details' => $result['details'] ?? null
    ], 500);
}

/**
 * Testează conexiunea SMTP
 */
function testSmtpConnection($settings) {
    $host = $settings['smtp_host'];
    $port = intval($settings['smtp_port']);
    $username = $settings['smtp_username'];
    $password = $settings['smtp_password'];
    $encryption = $settings['smtp_encryption'];

    // Verifică PHPMailer
    $phpmailerPath = __DIR__ . '/../vendor/autoload.php';

    if (file_exists($phpmailerPath)) {
        require_once $phpmailerPath;

        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);

            // Setări server
            $mail->isSMTP();
            $mail->Host = $host;
            $mail->SMTPAuth = true;
            $mail->Username = $username;
            $mail->Password = $password;
            $mail->Port = $port;

            if ($encryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($encryption === 'tls') {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPSecure = false;
                $mail->SMTPAutoTLS = false;
            }

            // Timeout scurt pentru test
            $mail->Timeout = 10;

            // Încearcă conectarea
            $mail->smtpConnect();
            $mail->smtpClose();

            return [
                'success' => true,
                'details' => [
                    'method' => 'PHPMailer',
                    'host' => $host,
                    'port' => $port,
                    'encryption' => $encryption
                ]
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $mail->ErrorInfo,
                'details' => [
                    'method' => 'PHPMailer',
                    'host' => $host,
                    'port' => $port
                ]
            ];
        }
    } else {
        // Fallback: test socket simplu
        $context = stream_context_create();

        if ($encryption === 'ssl') {
            $connectHost = 'ssl://' . $host;
        } elseif ($encryption === 'tls') {
            $connectHost = 'tls://' . $host;
        } else {
            $connectHost = $host;
        }

        $errno = 0;
        $errstr = '';

        // Încearcă conexiune socket
        $socket = @stream_socket_client(
            $connectHost . ':' . $port,
            $errno,
            $errstr,
            10, // timeout 10 secunde
            STREAM_CLIENT_CONNECT,
            $context
        );

        if ($socket) {
            // Citește răspunsul serverului
            $response = fgets($socket, 512);
            fclose($socket);

            if (strpos($response, '220') !== false) {
                return [
                    'success' => true,
                    'details' => [
                        'method' => 'Socket',
                        'host' => $host,
                        'port' => $port,
                        'encryption' => $encryption,
                        'server_response' => trim($response)
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Server SMTP a răspuns neașteptat: ' . trim($response),
                    'details' => [
                        'method' => 'Socket',
                        'host' => $host,
                        'port' => $port
                    ]
                ];
            }
        } else {
            return [
                'success' => false,
                'error' => "Nu s-a putut conecta la server: $errstr (cod: $errno)",
                'details' => [
                    'method' => 'Socket',
                    'host' => $host,
                    'port' => $port
                ]
            ];
        }
    }
}
