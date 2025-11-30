<?php
/**
 * PANOUL ADMIN SIMPLIFICAT - Funcționează ÎNAINTE de upgrade complet
 * Afișează doar statistici de bază și link către upgrade
 */

session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verifică autentificare
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Obține date utilizator curent
$currentUser = dbFetchOne("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
if (!$currentUser) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Verifică dacă upgrade-ul a fost făcut (verifică dacă există câmpul is_admin)
$upgradeComplete = isset($currentUser['is_admin']);

// Obține statistici de bază (doar din tabelele existente)
$stats = [
    'manifests' => dbFetchOne("SELECT COUNT(*) as count FROM manifests")['count'] ?? 0,
    'containers' => dbFetchOne("SELECT COUNT(*) as count FROM manifest_entries")['count'] ?? 0,
    'ships' => dbFetchOne("SELECT COUNT(*) as count FROM ships")['count'] ?? 0,
    'users' => dbFetchOne("SELECT COUNT(*) as count FROM users")['count'] ?? 0,
];
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Registru Import RE1</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar-brand {
            font-weight: 600;
            font-size: 1.3rem;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .stat-card h3 {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0;
        }
        .stat-card p {
            margin: 5px 0 0 0;
            opacity: 0.9;
        }
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .success-box {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .btn-upgrade {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border: none;
            color: white;
            padding: 15px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 8px;
        }
        .btn-upgrade:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(245, 87, 108, 0.4);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="bi bi-speedometer2"></i> Admin Panel
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text text-white me-3">
                    <i class="bi bi-person-circle"></i> <?= sanitize($currentUser['username']) ?>
                </span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1 class="mb-4">Panou de Administrare</h1>

        <?php if (!$upgradeComplete): ?>
        <!-- Mesaj UPGRADE NECESAR -->
        <div class="warning-box">
            <h4><i class="bi bi-exclamation-triangle-fill"></i> Upgrade Necesar!</h4>
            <p class="mb-3">Baza de date necesită upgrade pentru a activa toate funcționalitățile avansate.</p>
            <p class="mb-3"><strong>Upgrade-ul va adăuga:</strong></p>
            <ul>
                <li>Câmpuri pentru permisiuni utilizatori (is_admin, is_active)</li>
                <li>Gestionare ani (database_years)</li>
                <li>Pavilioane nave (pavilions)</li>
                <li>Tipuri containere cu imagini (container_types)</li>
                <li>Template-uri import personalizate (import_templates)</li>
                <li>Log-uri import detaliate (import_logs)</li>
            </ul>
            <p class="mb-0">
                <a href="quick_upgrade.php" class="btn btn-upgrade">
                    <i class="bi bi-rocket-takeoff"></i> Rulează Quick Upgrade
                </a>
                <a href="install_upgrade.php" class="btn btn-secondary ms-2">
                    <i class="bi bi-gear"></i> Upgrade Complet
                </a>
            </p>
        </div>
        <?php else: ?>
        <!-- Mesaj SUCCES -->
        <div class="success-box">
            <h4><i class="bi bi-check-circle-fill"></i> Upgrade Complet!</h4>
            <p class="mb-0">Baza de date este actualizată. Toate funcționalitățile sunt disponibile!</p>
            <p class="mb-0 mt-2">
                <a href="admin_new.php" class="btn btn-success">
                    <i class="bi bi-grid"></i> Deschide Admin Panel Complet
                </a>
            </p>
        </div>
        <?php endif; ?>

        <!-- Statistici de bază -->
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <h3><?= number_format($stats['manifests']) ?></h3>
                    <p>Manifesturi</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <h3><?= number_format($stats['containers']) ?></h3>
                    <p>Containere</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <h3><?= number_format($stats['ships']) ?></h3>
                    <p>Nave</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                    <h3><?= number_format($stats['users']) ?></h3>
                    <p>Utilizatori</p>
                </div>
            </div>
        </div>

        <!-- Link-uri rapide -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-link-45deg"></i> Link-uri Rapide</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <a href="index.php" class="btn btn-outline-primary w-100 mb-2">
                                    <i class="bi bi-house"></i> Pagina Principală
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="admin.php" class="btn btn-outline-secondary w-100 mb-2">
                                    <i class="bi bi-grid"></i> Admin Vechi
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="test_login.php" class="btn btn-outline-info w-100 mb-2">
                                    <i class="bi bi-bug"></i> Test Login
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="logout.php" class="btn btn-outline-danger w-100 mb-2">
                                    <i class="bi bi-box-arrow-right"></i> Logout
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Informații utilizator -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="bi bi-person-badge"></i> Informații Utilizator Curent</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr>
                                <th style="width: 200px;">Username:</th>
                                <td><?= sanitize($currentUser['username']) ?></td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td><?= sanitize($currentUser['email'] ?? 'N/A') ?></td>
                            </tr>
                            <?php if (isset($currentUser['is_admin'])): ?>
                            <tr>
                                <th>Administrator:</th>
                                <td>
                                    <?php if ($currentUser['is_admin']): ?>
                                        <span class="badge bg-success">Da</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Nu</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Activ:</th>
                                <td>
                                    <?php if ($currentUser['is_active']): ?>
                                        <span class="badge bg-success">Da</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Nu</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <th>Ultimul login:</th>
                                <td><?= sanitize($currentUser['last_login'] ?? 'N/A') ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
