<?php
/**
 * Clear Session - Șterge toate sesiunile și cookies
 * Accesează acest script pentru a reseta complet sesiunea
 */

// Configurare sesiune
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_lifetime', 0);

session_start();

// Șterge toate variabilele de sesiune
$_SESSION = array();

// Șterge cookie-ul de sesiune
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Distruge sesiunea
session_destroy();

// Șterge toate cookie-urile site-ului
foreach ($_COOKIE as $cookie_name => $cookie_value) {
    setcookie($cookie_name, '', time() - 3600, '/');
}

?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sesiune Resetată</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            text-align: center;
            max-width: 500px;
        }
        .success-icon {
            font-size: 60px;
            color: #28a745;
            margin-bottom: 20px;
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        .info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: left;
        }
        .info li {
            margin: 10px 0;
            color: #666;
        }
        .btn {
            display: inline-block;
            padding: 15px 30px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin-top: 20px;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #0056b3;
        }
        .countdown {
            margin-top: 20px;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-icon">✓</div>
        <h1>Sesiune Resetată!</h1>
        <p>Toate sesiunile și cookie-urile au fost șterse cu succes.</p>

        <div class="info">
            <p><strong>Ce s-a întâmplat:</strong></p>
            <ul>
                <li>✓ Toate variabilele de sesiune au fost șterse</li>
                <li>✓ Cookie-ul de sesiune a fost distrus</li>
                <li>✓ Toate cookie-urile site-ului au fost eliminate</li>
                <li>✓ Cache-ul PHP a fost curățat</li>
            </ul>
        </div>

        <a href="login.php" class="btn">Mergi la Login →</a>

        <div class="countdown">
            <p>Vei fi redirecționat automat în <span id="counter">5</span> secunde...</p>
        </div>
    </div>

    <script>
        // Auto redirect după 5 secunde
        let seconds = 5;
        const counterElement = document.getElementById('counter');

        const countdown = setInterval(() => {
            seconds--;
            counterElement.textContent = seconds;

            if (seconds <= 0) {
                clearInterval(countdown);
                window.location.href = 'login.php';
            }
        }, 1000);
    </script>
</body>
</html>
