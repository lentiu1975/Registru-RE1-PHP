<?php
/**
 * QUICK UPGRADE - Doar câmpurile esențiale pentru login
 * Acest script adaugă rapid câmpurile necesare fără date inițiale
 */

require_once 'config/database.php';

echo "<!DOCTYPE html>
<html lang='ro'>
<head>
    <meta charset='UTF-8'>
    <title>Quick Upgrade</title>
    <style>
        body { font-family: Arial; max-width: 900px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; }
        .btn { padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px; }
    </style>
</head>
<body>
<div class='card'>
<h1>⚡ Quick Upgrade - Esențial pentru Login</h1>
<hr>
";

$conn = getDbConnection();
if (!$conn) {
    echo "<p class='error'>✗ Nu s-a putut conecta la baza de date!</p>";
    echo "</div></body></html>";
    exit;
}

echo "<p class='success'>✓ Conectat la: " . DB_NAME . "</p>";
echo "<br>";

$success = 0;
$errors = [];

// 1. Adaugă câmpuri în tabela users
echo "<h3>1. Adaugă câmpuri în tabela 'users'</h3>";

$userFields = [
    "ALTER TABLE users ADD COLUMN full_name VARCHAR(200) DEFAULT NULL AFTER email",
    "ALTER TABLE users ADD COLUMN company_name VARCHAR(200) DEFAULT NULL AFTER full_name",
    "ALTER TABLE users ADD COLUMN is_active TINYINT(1) DEFAULT 1 AFTER company_name",
    "ALTER TABLE users ADD COLUMN is_admin TINYINT(1) DEFAULT 0 AFTER is_active",
    "ALTER TABLE users ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER last_login"
];

foreach ($userFields as $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "✓ " . substr($sql, 0, 60) . "...<br>";
        $success++;
    } else {
        $error = $conn->error;
        if (stripos($error, 'Duplicate column') !== false) {
            echo "⚠ SKIP: Coloana deja există<br>";
            $success++;
        } else {
            echo "✗ ERROR: $error<br>";
            $errors[] = $error;
        }
    }
}

// 2. Setează admin ca administrator
echo "<br><h3>2. Setează utilizatorul 'admin' ca administrator</h3>";
$sql = "UPDATE users SET is_admin = 1, is_active = 1 WHERE username = 'admin'";
if ($conn->query($sql) === TRUE) {
    echo "<p class='success'>✓ Utilizatorul 'admin' este acum administrator activ!</p>";
    $success++;
} else {
    echo "<p class='error'>✗ ERROR: " . $conn->error . "</p>";
    $errors[] = $conn->error;
}

// 3. Verificare
echo "<br><h3>3. Verificare Rezultat</h3>";
$result = $conn->query("SELECT * FROM users WHERE username = 'admin'");
if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo "<pre>";
    echo "Username: " . $user['username'] . "\n";
    echo "Email: " . ($user['email'] ?? 'NULL') . "\n";
    echo "Full Name: " . ($user['full_name'] ?? 'NULL') . "\n";
    echo "Company Name: " . ($user['company_name'] ?? 'NULL') . "\n";
    echo "Is Active: " . ($user['is_active'] ?? 'LIPSĂ') . "\n";
    echo "Is Admin: " . ($user['is_admin'] ?? 'LIPSĂ') . "\n";
    echo "</pre>";

    if (isset($user['is_admin']) && $user['is_admin'] == 1) {
        echo "<p class='success'>✓✓✓ SUCCES COMPLET! Admin-ul are toate permisiunile!</p>";
    }
}

$conn->close();

// Rezumat
echo "<br><hr>";
echo "<h2>📊 Rezumat</h2>";
echo "<p>Comenzi executate cu succes: <strong>$success</strong></p>";

if (count($errors) > 0) {
    echo "<p>Erori: <strong>" . count($errors) . "</strong></p>";
    echo "<pre>" . implode("\n", $errors) . "</pre>";
}

echo "<br>";
if (count($errors) == 0 || $success >= 5) {
    echo "<p class='success' style='font-size: 20px;'>✓ UPGRADE COMPLETAT!</p>";
    echo "<p>Acum poți face login pe admin panel-ul nou!</p>";
    echo "<br>";
    echo "<a href='admin_new.php' class='btn' style='background: #28a745; font-size: 18px;'>🚀 Deschide Admin Panel</a> ";
    echo "<a href='login.php' class='btn'>Login</a>";
} else {
    echo "<p class='error'>⚠ UPGRADE PARȚIAL - Verifică erorile de mai sus</p>";
    echo "<a href='test_login.php' class='btn'>Test Login</a>";
}

echo "
</div>
</body>
</html>";
?>
