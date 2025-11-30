<?php
/**
 * Test direct acces la admin.php
 */
session_start();

// Setează manual sesiunea pentru a simula login
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['last_activity'] = time();

echo "<!DOCTYPE html>";
echo "<html lang='ro'>";
echo "<head><meta charset='UTF-8'><title>Test Admin Direct</title></head>";
echo "<body style='font-family: Arial; padding: 20px;'>";
echo "<h1>Test Admin Direct Access</h1>";
echo "<pre>";
echo "Session setată manual:\n";
echo "  - user_id: " . $_SESSION['user_id'] . "\n";
echo "  - username: " . $_SESSION['username'] . "\n";
echo "</pre>";

echo "<h2>Test 1: Link direct</h2>";
echo "<a href='/admin.php' style='font-size: 18px; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; display: inline-block;'>Click aici pentru admin.php</a>";
echo "<br><br>";

echo "<h2>Test 2: JavaScript redirect (3 secunde)</h2>";
echo "<p>Vei fi redirecționat automat în 3 secunde...</p>";
echo "<script>setTimeout(function() { window.location.href = '/admin.php'; }, 3000);</script>";
echo "<br>";

echo "<h2>Test 3: Meta refresh (5 secunde)</h2>";
echo "<p>Dacă JavaScript nu funcționează, vei fi redirecționat în 5 secunde...</p>";
echo "<meta http-equiv='refresh' content='5;url=/admin.php'>";
echo "<br>";

echo "<h2>Test 4: Verifică dacă fișierul există</h2>";
if (file_exists(__DIR__ . '/admin.php')) {
    echo "<p style='color: green;'>✓ Fișierul admin.php EXISTĂ!</p>";
    echo "<p>Calea completă: " . __DIR__ . "/admin.php</p>";
} else {
    echo "<p style='color: red;'>✗ Fișierul admin.php NU EXISTĂ!</p>";
}

echo "</body></html>";
?>
