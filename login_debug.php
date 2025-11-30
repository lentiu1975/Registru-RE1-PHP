<?php
/**
 * DEBUG LOGIN - Verifică tot procesul de login pas cu pas
 */

// Activează afișarea erorilor
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<h1>DEBUG LOGIN</h1>";
echo "<pre>";

// Verifică POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "=== REQUEST POST DETECTAT ===\n\n";

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    echo "Username introdus: " . $username . "\n";
    echo "Parolă introdusă: " . (empty($password) ? 'GOALĂ' : '***') . "\n\n";

    if (empty($username) || empty($password)) {
        echo "EROARE: Câmpuri goale!\n";
    } else {
        echo "=== CĂUTARE UTILIZATOR ÎN BAZA DE DATE ===\n";

        try {
            $sql = "SELECT * FROM users WHERE username = ?";
            $user = dbFetchOne($sql, [$username]);

            if ($user) {
                echo "✓ Utilizator găsit!\n";
                echo "  - ID: " . $user['id'] . "\n";
                echo "  - Username: " . $user['username'] . "\n";
                echo "  - Hash parolă: " . substr($user['password'], 0, 30) . "...\n\n";

                echo "=== VERIFICARE PAROLĂ ===\n";
                $passwordValid = verifyPassword($password, $user['password']);

                if ($passwordValid) {
                    echo "✓✓✓ PAROLĂ CORECTĂ!\n\n";

                    echo "=== SETARE SESIUNE ===\n";
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['last_activity'] = time();

                    echo "✓ Session user_id: " . $_SESSION['user_id'] . "\n";
                    echo "✓ Session username: " . $_SESSION['username'] . "\n";
                    echo "✓ Session last_activity: " . $_SESSION['last_activity'] . "\n\n";

                    echo "=== UPDATE LAST_LOGIN ===\n";
                    try {
                        $updateSql = "UPDATE users SET last_login = NOW() WHERE id = ?";
                        $result = dbQuery($updateSql, [$user['id']]);
                        echo "✓ Last login actualizat\n\n";
                    } catch (Exception $e) {
                        echo "⚠ Eroare la update last_login: " . $e->getMessage() . "\n\n";
                    }

                    echo "=== REDIRECȚIONARE ===\n";
                    echo "Ar trebui să redirecționez către: /admin.php\n\n";

                    echo "<strong style='color: green; font-size: 18px;'>LOGIN REUȘIT!</strong>\n\n";
                    echo "Click aici pentru a merge la admin: <a href='/admin.php'>Admin Panel</a>\n\n";

                    echo "=== TEST REDIRECT ===\n";
                    echo "Voi încerca redirect în 3 secunde...\n";

                    // Redirecționare cu meta refresh
                    echo "</pre>";
                    echo "<meta http-equiv='refresh' content='3;url=/admin.php'>";
                    exit;

                } else {
                    echo "✗ PAROLĂ INCORECTĂ!\n";
                }
            } else {
                echo "✗ Utilizatorul NU EXISTĂ!\n";
            }
        } catch (Exception $e) {
            echo "EXCEPȚIE: " . $e->getMessage() . "\n";
            echo "Trace: " . $e->getTraceAsString() . "\n";
        }
    }
} else {
    echo "Request Method: " . $_SERVER['REQUEST_METHOD'] . "\n\n";
    echo "Formularul nu a fost trimis încă.\n\n";
}

echo "=== STARE SESIUNE ACTUALĂ ===\n";
echo "Session ID: " . session_id() . "\n";
echo "Session user_id: " . ($_SESSION['user_id'] ?? 'NU EXISTĂ') . "\n";
echo "Session username: " . ($_SESSION['username'] ?? 'NU EXISTĂ') . "\n";

echo "</pre>";
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Debug Login</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        form { background: white; padding: 20px; border-radius: 5px; margin-top: 20px; }
        input { padding: 10px; margin: 5px 0; width: 300px; display: block; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <h2>Formular Login Debug</h2>
    <form method="POST">
        <label>Username:</label>
        <input type="text" name="username" value="admin" required>

        <label>Parolă:</label>
        <input type="password" name="password" value="admin123" required>

        <button type="submit">Login</button>
    </form>
</body>
</html>
