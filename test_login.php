<?php
/**
 * Script de test pentru login - DEBUG
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<h1>Test Login Debug</h1>";
echo "<hr>";

// Test conexiune database
echo "<h2>1. Test Conexiune Database</h2>";
$conn = getDbConnection();
if ($conn) {
    echo "✓ Conexiune reușită la baza de date: " . DB_NAME . "<br>";
    $conn->close();
} else {
    echo "✗ EROARE: Nu s-a putut conecta la baza de date!<br>";
    die();
}

// Test tabela users
echo "<h2>2. Test Tabelă Users</h2>";
$checkTable = dbQuery("SHOW TABLES LIKE 'users'");
if ($checkTable && $checkTable->num_rows > 0) {
    echo "✓ Tabela 'users' există<br>";
} else {
    echo "✗ EROARE: Tabela 'users' nu există!<br>";
    die();
}

// Test utilizator admin
echo "<h2>3. Test Utilizator Admin</h2>";
$user = dbFetchOne("SELECT * FROM users WHERE username = 'admin'");

if ($user) {
    echo "✓ Utilizator 'admin' găsit!<br>";
    echo "<pre>";
    echo "ID: " . $user['id'] . "\n";
    echo "Username: " . $user['username'] . "\n";
    echo "Email: " . ($user['email'] ?? 'NULL') . "\n";
    echo "Password Hash: " . substr($user['password'], 0, 30) . "...\n";
    echo "Created At: " . $user['created_at'] . "\n";

    // Verifică câmpuri noi
    if (isset($user['is_admin'])) {
        echo "is_admin: " . $user['is_admin'] . " (Upgrade rulat!)\n";
    } else {
        echo "is_admin: LIPSĂ (Upgrade NU a fost rulat!)\n";
    }

    if (isset($user['is_active'])) {
        echo "is_active: " . $user['is_active'] . " (Upgrade rulat!)\n";
    } else {
        echo "is_active: LIPSĂ (Upgrade NU a fost rulat!)\n";
    }
    echo "</pre>";
} else {
    echo "✗ EROARE: Utilizatorul 'admin' nu există!<br>";
    echo "<br><strong>Soluție:</strong> Crează utilizatorul admin manual:<br>";
    echo "<pre>";
    echo "INSERT INTO users (username, password) VALUES ('admin', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');";
    echo "</pre>";
    die();
}

// Test verificare parolă
echo "<h2>4. Test Verificare Parolă</h2>";
$testPassword = 'admin123';
$passwordHash = $user['password'];

echo "Test parolă: <strong>admin123</strong><br>";
echo "Hash din DB: " . substr($passwordHash, 0, 50) . "...<br>";

if (verifyPassword($testPassword, $passwordHash)) {
    echo "✓ Parola este CORECTĂ!<br>";
    echo "<br><strong style='color: green;'>Login ar trebui să funcționeze!</strong><br>";
} else {
    echo "✗ Parola este INCORECTĂ!<br>";
    echo "<br><strong style='color: red;'>Problema: Hash-ul parolei nu este corect!</strong><br>";
    echo "<br><strong>Soluție:</strong> Resetează parola admin:<br>";
    echo "<pre>";
    $newHash = password_hash('admin123', PASSWORD_DEFAULT);
    echo "UPDATE users SET password = '$newHash' WHERE username = 'admin';";
    echo "</pre>";
}

// Test generare hash nou
echo "<h2>5. Generare Hash Nou</h2>";
$newHash = password_hash('admin123', PASSWORD_DEFAULT);
echo "Hash nou generat pentru parola 'admin123':<br>";
echo "<pre>" . $newHash . "</pre>";
echo "<br>";
echo "SQL pentru update:<br>";
echo "<pre>UPDATE users SET password = '$newHash' WHERE username = 'admin';</pre>";

// Test verificare câmpuri noi
echo "<h2>6. Verificare Câmpuri Noi (Upgrade)</h2>";
$columns = dbFetchAll("SHOW COLUMNS FROM users");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Câmp</th><th>Tip</th><th>Null</th><th>Default</th></tr>";
foreach ($columns as $column) {
    $highlight = '';
    if (in_array($column['Field'], ['is_admin', 'is_active', 'full_name', 'company_name'])) {
        $highlight = ' style="background-color: #d4edda;"';
    }
    echo "<tr$highlight>";
    echo "<td>" . $column['Field'] . "</td>";
    echo "<td>" . $column['Type'] . "</td>";
    echo "<td>" . $column['Null'] . "</td>";
    echo "<td>" . ($column['Default'] ?? 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";
echo "<br>";
echo "<strong>Câmpuri noi (cu verde):</strong> is_admin, is_active, full_name, company_name<br>";
echo "Dacă nu vezi câmpurile cu verde, upgrade-ul NU a fost rulat!<br>";

echo "<hr>";
echo "<h2>Concluzie</h2>";
echo "Dacă toate testele de mai sus sunt ✓, atunci login-ul ar trebui să funcționeze.<br>";
echo "<br>";
echo "<a href='login.php' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Încearcă Login</a> ";
echo "<a href='install_upgrade.php' style='padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px;'>Rulează Upgrade</a>";
