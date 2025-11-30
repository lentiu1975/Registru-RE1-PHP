<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upgrade Bază de Date - Registru Import RE1</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 900px;
            width: 100%;
            padding: 40px;
        }
        h1 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .status-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .command {
            background: #2d3748;
            color: #e2e8f0;
            padding: 12px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            margin: 5px 0;
            white-space: pre-wrap;
        }
        .success { color: #48bb78; font-weight: bold; }
        .error { color: #f56565; font-weight: bold; }
        .warning { color: #ed8936; font-weight: bold; }
        .btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-block;
            margin-top: 20px;
        }
        .btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn:disabled {
            background: #cbd5e0;
            cursor: not-allowed;
            transform: none;
        }
        .result {
            margin-top: 20px;
            max-height: 500px;
            overflow-y: auto;
        }
        .summary {
            background: #e6fffa;
            border-left: 4px solid #38b2ac;
            padding: 15px;
            margin-top: 20px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Upgrade Bază de Date</h1>
        <p class="subtitle">Registru Import RE1 - Adăugare funcționalități avansate</p>

        <div class="status-box">
            <h3>Funcționalități care vor fi adăugate:</h3>
            <ul style="margin-top: 10px; margin-left: 20px; line-height: 1.8;">
                <li>✓ Database Years (gestionare ani baze de date)</li>
                <li>✓ Pavilions (pavilioane nave cu steaguri)</li>
                <li>✓ Container Types avansate (cu imagini)</li>
                <li>✓ Import Templates (template-uri personalizate)</li>
                <li>✓ Câmpuri noi în manifest_entries (observations, current_number)</li>
                <li>✓ Câmpuri noi în users (full_name, company_name, permisiuni)</li>
                <li>✓ Îmbunătățiri import_logs (user_id, template_id, status)</li>
            </ul>
        </div>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_upgrade'])) {
            echo '<div class="result">';
            echo '<h3>Rezultat Upgrade:</h3>';

            require_once 'config/database.php';

            // Citește fișierul SQL
            $sqlFile = __DIR__ . '/upgrade_database.sql';
            if (!file_exists($sqlFile)) {
                echo '<div class="command error">ERROR: Fișierul upgrade_database.sql nu a fost găsit!</div>';
                echo '</div></div></body></html>';
                exit;
            }

            $sql = file_get_contents($sqlFile);
            if ($sql === false) {
                echo '<div class="command error">ERROR: Nu s-a putut citi fișierul upgrade_database.sql!</div>';
                echo '</div></div></body></html>';
                exit;
            }

            // Conectare
            $conn = getDbConnection();
            if (!$conn) {
                echo '<div class="command error">ERROR: Nu s-a putut conecta la baza de date!</div>';
                echo '</div></div></body></html>';
                exit;
            }

            echo '<div class="command success">✓ Conectat la baza de date: ' . htmlspecialchars(DB_NAME) . '</div>';

            // Împarte SQL-ul în comenzi
            $lines = explode("\n", $sql);
            $commands = [];
            $currentCommand = '';

            foreach ($lines as $line) {
                $line = trim($line);

                // Sare peste comentarii și linii goale
                if (empty($line) || strpos($line, '--') === 0) {
                    continue;
                }

                $currentCommand .= $line . ' ';

                // Dacă linia se termină cu ;, atunci avem o comandă completă
                if (substr($line, -1) === ';') {
                    $commands[] = trim($currentCommand);
                    $currentCommand = '';
                }
            }

            // Rulează fiecare comandă
            $success = 0;
            $failed = 0;
            $skipped = 0;
            $total = count($commands);

            echo '<div class="command">Se procesează ' . $total . ' comenzi SQL...</div>';

            foreach ($commands as $i => $command) {
                if (empty(trim($command))) {
                    continue;
                }

                $commandPreview = substr($command, 0, 80);
                if (strlen($command) > 80) {
                    $commandPreview .= '...';
                }

                $commandNum = $i + 1;

                if ($conn->query($command) === TRUE) {
                    $success++;
                    echo '<div class="command success">[' . $commandNum . '/' . $total . '] ✓ ' . htmlspecialchars($commandPreview) . '</div>';
                } else {
                    $error = $conn->error;
                    // Ignorăm erorile pentru comenzi care pot eșua
                    if (stripos($error, 'Duplicate column') !== false ||
                        stripos($error, 'Duplicate key') !== false ||
                        stripos($error, 'already exists') !== false) {
                        $skipped++;
                        echo '<div class="command warning">[' . $commandNum . '/' . $total . '] ⚠ SKIPPED: ' . htmlspecialchars($commandPreview) . '</div>';
                    } else {
                        $failed++;
                        echo '<div class="command error">[' . $commandNum . '/' . $total . '] ✗ ERROR: ' . htmlspecialchars($commandPreview) . '<br>   ' . htmlspecialchars($error) . '</div>';
                    }
                }
            }

            $conn->close();

            // Rezumat
            echo '<div class="summary">';
            echo '<h3>📊 Rezumat Upgrade:</h3>';
            echo '<ul style="margin-top: 10px; margin-left: 20px; line-height: 1.8;">';
            echo '<li>Total comenzi: ' . $total . '</li>';
            echo '<li class="success">Succes: ' . $success . '</li>';
            echo '<li class="warning">Sărite (deja existente): ' . $skipped . '</li>';
            echo '<li class="error">Eșuate: ' . $failed . '</li>';
            echo '</ul>';

            if ($failed === 0) {
                echo '<p style="margin-top: 15px;" class="success">✓ UPGRADE COMPLETAT CU SUCCES!</p>';
                echo '<p style="margin-top: 10px;"><a href="admin.php" class="btn">Accesează Panoul Admin</a></p>';
            } else {
                echo '<p style="margin-top: 15px;" class="error">⚠ UPGRADE COMPLETAT CU ERORI!</p>';
                echo '<p>Verifică erorile de mai sus.</p>';
            }
            echo '</div>';

            echo '</div>'; // .result
        } else {
            ?>
            <form method="POST">
                <input type="hidden" name="run_upgrade" value="1">
                <button type="submit" class="btn">🚀 Începe Upgrade</button>
            </form>
            <?php
        }
        ?>
    </div>
</body>
</html>
