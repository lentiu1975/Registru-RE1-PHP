<?php
/**
 * Script de resetare parolă pentru admin
 * Rulează o singură dată pentru a reseta parola la admin123
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<!DOCTYPE html>
<html lang='ro'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Reset Parolă Admin</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #007bff; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
        }
        .btn-success { background: #28a745; }
        .btn-danger { background: #dc3545; }
    </style>
</head>
<body>
<div class='card'>
";

echo "<h1>🔒 Resetare Parolă Admin</h1>";
echo "<hr>";

// Verifică dacă avem parametrul de confirmare
if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {

    echo "<h2>Se resetează parola...</h2>";

    // Generează hash nou pentru parola admin123
    $newPassword = 'admin123';
    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

    echo "<p class='info'>Parolă nouă: <strong>$newPassword</strong></p>";
    echo "<p class='info'>Hash generat: <code>" . substr($newHash, 0, 50) . "...</code></p>";
    echo "<br>";

    // Încearcă să updateze parola
    try {
        $result = dbQuery("UPDATE users SET password = ? WHERE username = 'admin'", [$newHash]);

        if ($result) {
            echo "<p class='success'>✓ SUCCES! Parola pentru utilizatorul 'admin' a fost resetată!</p>";
            echo "<br>";
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; border-left: 4px solid #28a745;'>";
            echo "<strong>Credențiale noi:</strong><br>";
            echo "Username: <strong>admin</strong><br>";
            echo "Parolă: <strong>admin123</strong>";
            echo "</div>";
            echo "<br>";
            echo "<a href='login.php' class='btn btn-success'>Încearcă Login Acum</a>";
            echo "<a href='test_login.php' class='btn'>Test Login</a>";

            // Verifică rezultatul
            echo "<br><br>";
            echo "<h3>Verificare:</h3>";
            $user = dbFetchOne("SELECT * FROM users WHERE username = 'admin'");
            if ($user) {
                echo "<pre>";
                echo "Username: " . $user['username'] . "\n";
                echo "Hash nou: " . substr($user['password'], 0, 60) . "...\n";

                // Test verificare
                if (password_verify($newPassword, $user['password'])) {
                    echo "\n<span class='success'>✓ Verificare parolă: SUCCES!</span>\n";
                } else {
                    echo "\n<span class='error'>✗ Verificare parolă: EȘUAT!</span>\n";
                }
                echo "</pre>";
            }

        } else {
            echo "<p class='error'>✗ EROARE! Nu s-a putut reseta parola!</p>";
            echo "<p>Verifică conexiunea la baza de date.</p>";
        }

    } catch (Exception $e) {
        echo "<p class='error'>✗ EXCEPȚIE: " . $e->getMessage() . "</p>";
    }

} else {
    // Afișează formularul de confirmare

    echo "<p>Acest script va reseta parola pentru utilizatorul <strong>admin</strong>.</p>";
    echo "<br>";

    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107;'>";
    echo "<strong>⚠ ATENȚIE:</strong><br>";
    echo "Parola va fi resetată la: <strong>admin123</strong><br>";
    echo "Asigură-te că vrei să continui!";
    echo "</div>";

    echo "<br>";

    // Verifică starea actuală
    echo "<h3>Stare Actuală:</h3>";
    $user = dbFetchOne("SELECT * FROM users WHERE username = 'admin'");

    if ($user) {
        echo "<pre>";
        echo "Username: " . $user['username'] . "\n";
        echo "Password Hash: " . substr($user['password'], 0, 50) . "...\n";
        echo "Created: " . $user['created_at'] . "\n";

        if (isset($user['is_admin'])) {
            echo "Is Admin: " . ($user['is_admin'] ? 'Da' : 'Nu') . "\n";
        }

        if (isset($user['is_active'])) {
            echo "Is Active: " . ($user['is_active'] ? 'Da' : 'Nu') . "\n";
        }

        echo "</pre>";

        // Test cu parola curentă
        echo "<h4>Test Parolă Curentă:</h4>";
        $testPasswords = ['admin123', 'admin', 'password'];
        echo "<ul>";
        foreach ($testPasswords as $testPass) {
            $works = password_verify($testPass, $user['password']);
            if ($works) {
                echo "<li class='success'>✓ Parola '<strong>$testPass</strong>' FUNCȚIONEAZĂ</li>";
            } else {
                echo "<li>✗ Parola '$testPass' nu funcționează</li>";
            }
        }
        echo "</ul>";

    } else {
        echo "<p class='error'>✗ Utilizatorul 'admin' nu există în baza de date!</p>";
    }

    echo "<br>";
    echo "<h3>Continuă?</h3>";
    echo "<a href='?confirm=yes' class='btn btn-danger'>🔒 DA, Resetează Parola</a> ";
    echo "<a href='login.php' class='btn'>Anulează</a>";
}

echo "
</div>
</body>
</html>";
?>
