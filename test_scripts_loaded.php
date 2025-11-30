<?php
session_start();
$_SESSION['user_id'] = 1;
require_once 'config/database.php';
require_once 'includes/functions.php';

$currentUser = dbFetchOne("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
$stats = [
    'manifests' => dbFetchOne("SELECT COUNT(DISTINCT manifest_number) as count FROM manifests")['count'] ?? 0,
    'containers' => dbFetchOne("SELECT COUNT(*) as count FROM manifest_entries")['count'] ?? 0,
    'ships' => dbFetchOne("SELECT COUNT(*) as count FROM ships")['count'] ?? 0,
    'users' => dbFetchOne("SELECT COUNT(*) as count FROM users")['count'] ?? 0,
];
$activeYear = dbFetchOne("SELECT * FROM database_years WHERE is_active = 1 LIMIT 1");
$recentImport = dbFetchOne("SELECT * FROM import_logs ORDER BY created_at DESC LIMIT 1");
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Scripts Loaded</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Test: Care scripturi sunt încărcate?</h1>
        <div id="scripts-list" class="mt-4"></div>
    </div>

    <!-- EXACT same scripts as admin_new.php -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/import-excel.js?v=20250130"></script>
    <script src="assets/js/manifest-management.js?v=20250130"></script>

    <script>
        // Listează toate script-urile încărcate
        window.addEventListener('load', function() {
            const scripts = document.getElementsByTagName('script');
            let html = '<h3>Script-uri găsite în pagină (' + scripts.length + '):</h3><ol class="list-group list-group-numbered">';

            for (let i = 0; i < scripts.length; i++) {
                const src = scripts[i].src || '(inline script)';
                const type = scripts[i].type || 'text/javascript';

                let className = 'list-group-item';
                if (src.includes('modal.js') || src.includes('share-modal.js')) {
                    className += ' list-group-item-danger';
                }

                html += '<li class="' + className + '">';
                html += '<strong>Src:</strong> ' + src + '<br>';
                html += '<strong>Type:</strong> ' + type;
                html += '</li>';
            }

            html += '</ol>';

            html += '<div class="alert alert-info mt-4">';
            html += '<h4>Verificare în Network Tab:</h4>';
            html += '<p>Deschide Console (F12) → Tab "Network" → Filtrează după "JS"</p>';
            html += '<p>Caută după: modal.js, share-modal.js</p>';
            html += '</div>';

            document.getElementById('scripts-list').innerHTML = html;
        });

        // Log all errors
        window.addEventListener('error', function(e) {
            console.error('ERROR DETECTED:', e.message, 'from', e.filename);
        });
    </script>
</body>
</html>
