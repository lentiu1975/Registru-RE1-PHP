<?php
/**
 * PANOUL ADMIN COMPLET - Registru Import RE1
 * Toate funcționalitățile: Utilizatori, Ani, Pavilioane, Containere, Template-uri, Import, Export, Logs
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
$currentUser = dbFetchOne("SELECT * FROM users WHERE id = ? AND is_active = 1", [$_SESSION['user_id']]);
if (!$currentUser) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Verifică permisiuni admin - doar adminii pot accesa panoul de administrare
if (!$currentUser['is_admin']) {
    header('Location: index.php');
    exit;
}

// Obține statistici
$stats = [
    'manifests' => dbFetchOne("SELECT COUNT(DISTINCT manifest_number) as count FROM manifests")['count'] ?? 0,
    'containers' => dbFetchOne("SELECT COUNT(*) as count FROM manifest_entries")['count'] ?? 0,
    'ships' => dbFetchOne("SELECT COUNT(*) as count FROM ships")['count'] ?? 0,
    'users' => dbFetchOne("SELECT COUNT(*) as count FROM users")['count'] ?? 0,
    'pavilions' => dbFetchOne("SELECT COUNT(*) as count FROM pavilions")['count'] ?? 0,
    'container_types' => dbFetchOne("SELECT COUNT(*) as count FROM container_types")['count'] ?? 0,
];

// Obține anul activ
$activeYear = dbFetchOne("SELECT * FROM database_years WHERE is_active = 1 LIMIT 1");

// Obține import recent
$recentImport = dbFetchOne("SELECT * FROM import_logs ORDER BY created_at DESC LIMIT 1");
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Registru Import RE1</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 260px;
            background: linear-gradient(180deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }

        .sidebar-brand {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }

        .sidebar-brand h4 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }

        .sidebar-brand small {
            opacity: 0.8;
            font-size: 12px;
        }

        .sidebar-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-nav-item {
            padding: 0;
        }

        .sidebar-nav-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }

        .sidebar-nav-link:hover,
        .sidebar-nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left-color: white;
        }

        .sidebar-nav-link i {
            width: 24px;
            margin-right: 12px;
            font-size: 18px;
        }

        .main-content {
            margin-left: 260px;
            padding: 20px;
        }

        .top-bar {
            background: white;
            padding: 15px 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            transition: transform 0.2s;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .stats-card-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-bottom: 15px;
        }

        .stats-card h3 {
            font-size: 32px;
            font-weight: 700;
            margin: 0 0 5px 0;
        }

        .stats-card p {
            margin: 0;
            color: #6c757d;
            font-size: 14px;
        }

        .tab-content-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .btn-primary {
            background: var(--primary-color);
            border: none;
        }

        .btn-primary:hover {
            background: var(--secondary-color);
        }

        .table-actions {
            display: flex;
            gap: 5px;
        }

        .badge-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .year-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            display: inline-block;
            margin-right: 10px;
        }

        .year-badge.active {
            background: #d4edda;
            color: #155724;
        }

        .year-badge.inactive {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <h4>📦 Registru RE1</h4>
            <small>Administrare Completă</small>
        </div>

        <ul class="sidebar-nav">
            <li class="sidebar-nav-item">
                <a href="#dashboard" class="sidebar-nav-link active" data-tab="dashboard">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="#manifests" class="sidebar-nav-link" data-tab="manifests">
                    <i class="bi bi-file-earmark-text"></i>
                    <span>Manifeste</span>
                </a>
            </li>
            <?php if ($currentUser['is_admin']): ?>
            <li class="sidebar-nav-item">
                <a href="#users" class="sidebar-nav-link" data-tab="users">
                    <i class="bi bi-people"></i>
                    <span>Utilizatori</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="#database-years" class="sidebar-nav-link" data-tab="database-years">
                    <i class="bi bi-calendar-range"></i>
                    <span>Ani Baze Date</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="#pavilions" class="sidebar-nav-link" data-tab="pavilions">
                    <i class="bi bi-flag"></i>
                    <span>Pavilioane</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="#container-types" class="sidebar-nav-link" data-tab="container-types">
                    <i class="bi bi-box-seam"></i>
                    <span>Tipuri Containere</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="#import-templates" class="sidebar-nav-link" data-tab="import-templates">
                    <i class="bi bi-file-earmark-code"></i>
                    <span>Template-uri Import</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="#email-settings" class="sidebar-nav-link" data-tab="email-settings">
                    <i class="bi bi-envelope-at"></i>
                    <span>Setări Email</span>
                </a>
            </li>
            <?php endif; ?>
            <li class="sidebar-nav-item">
                <a href="#import-excel" class="sidebar-nav-link" data-tab="import-excel">
                    <i class="bi bi-file-earmark-arrow-up"></i>
                    <span>Import Excel</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="#export-data" class="sidebar-nav-link" data-tab="export-data">
                    <i class="bi bi-file-earmark-arrow-down"></i>
                    <span>Export Date</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="#import-logs" class="sidebar-nav-link" data-tab="import-logs">
                    <i class="bi bi-clock-history"></i>
                    <span>Istoric Import</span>
                </a>
            </li>
        </ul>

        <div style="position: absolute; bottom: 20px; left: 20px; right: 20px;">
            <div style="padding: 15px; background: rgba(255,255,255,0.1); border-radius: 8px;">
                <div style="font-size: 12px; opacity: 0.8;">Conectat ca:</div>
                <div style="font-weight: 600; margin-top: 5px;"><?= htmlspecialchars($currentUser['username']) ?></div>
                <?php if ($currentUser['is_admin']): ?>
                <span class="badge bg-warning text-dark mt-2">Administrator</span>
                <?php endif; ?>
            </div>
            <a href="logout.php" class="btn btn-light btn-sm w-100 mt-2">
                <i class="bi bi-box-arrow-right"></i> Deconectare
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div>
                <h3 style="margin: 0;">Bine ai venit!</h3>
                <small class="text-muted">
                    <?php if ($activeYear): ?>
                    An activ: <strong><?= $activeYear['year'] ?></strong>
                    <?php else: ?>
                    <span class="text-warning">Niciun an activ selectat</span>
                    <?php endif; ?>
                </small>
            </div>
            <div>
                <a href="index.php" class="btn btn-outline-primary">
                    <i class="bi bi-house"></i> Pagina Principală
                </a>
            </div>
        </div>

        <!-- Dashboard Tab -->
        <div id="dashboard-tab" class="tab-pane-content">
            <div class="row">
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-card-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; font-size: 2rem;">
                            📄
                        </div>
                        <h3><?= number_format($stats['manifests']) ?></h3>
                        <p>Total Manifeste</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-card-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; font-size: 2rem;">
                            📦
                        </div>
                        <h3><?= number_format($stats['containers']) ?></h3>
                        <p>Total Containere</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-card-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; font-size: 2rem;">
                            🚢
                        </div>
                        <h3><?= number_format($stats['ships']) ?></h3>
                        <p>Total Nave</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-card-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; font-size: 2rem;">
                            👥
                        </div>
                        <h3><?= number_format($stats['users']) ?></h3>
                        <p>Total Utilizatori</p>
                    </div>
                </div>
            </div>

            <?php if ($currentUser['is_admin']): ?>
            <div class="row mt-4">
                <div class="col-md-4">
                    <div class="stats-card">
                        <div class="stats-card-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; font-size: 2rem;">
                            🚩
                        </div>
                        <h3><?= number_format($stats['pavilions']) ?></h3>
                        <p>Pavilioane</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card">
                        <div class="stats-card-icon" style="background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); color: white; font-size: 2rem;">
                            📦
                        </div>
                        <h3><?= number_format($stats['container_types']) ?></h3>
                        <p>Tipuri Containere</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card">
                        <div class="stats-card-icon" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); color: #333; font-size: 2rem;">
                            🕐
                        </div>
                        <h3><?= $recentImport ? date('d.m.Y', strtotime($recentImport['created_at'])) : 'N/A' ?></h3>
                        <p>Ultimul Import</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="tab-content-section mt-4">
                <h5 class="mb-3">Acțiuni Rapide</h5>
                <div class="row">
                    <div class="col-md-4">
                        <button class="btn btn-primary w-100" onclick="switchTab('import-excel')">
                            <i class="bi bi-file-earmark-arrow-up"></i> Import Nou
                        </button>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-outline-primary w-100" onclick="switchTab('export-data')">
                            <i class="bi bi-file-earmark-arrow-down"></i> Export Date
                        </button>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-outline-primary w-100" onclick="switchTab('manifests')">
                            <i class="bi bi-search"></i> Caută Manifest
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Manifests Tab -->
        <div id="manifests-tab" class="tab-pane-content" style="display: none;">
            <div class="tab-content-section">
                <div id="manifests-view-container">
                    <p class="text-center text-muted">Se încarcă...</p>
                </div>
            </div>
        </div>

        <!-- Users Tab (Admin only) -->
        <?php if ($currentUser['is_admin']): ?>
        <div id="users-tab" class="tab-pane-content" style="display: none;">
            <div class="tab-content-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5>Gestionare Utilizatori</h5>
                    <button class="btn btn-primary" onclick="showUserModal()">
                        <i class="bi bi-plus-circle"></i> Utilizator Nou
                    </button>
                </div>
                <div id="users-table-container">
                    <p class="text-center text-muted">Se încarcă...</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Database Years Tab (Admin only) -->
        <?php if ($currentUser['is_admin']): ?>
        <div id="database-years-tab" class="tab-pane-content" style="display: none;">
            <div class="tab-content-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5>Gestionare Ani Baze Date</h5>
                    <button class="btn btn-primary" onclick="showYearModal()">
                        <i class="bi bi-plus-circle"></i> An Nou
                    </button>
                </div>
                <div id="years-table-container">
                    <p class="text-center text-muted">Se încarcă...</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Pavilions Tab (Admin only) -->
        <?php if ($currentUser['is_admin']): ?>
        <div id="pavilions-tab" class="tab-pane-content" style="display: none;">
            <div class="tab-content-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5>Gestionare Pavilioane</h5>
                    <button class="btn btn-primary" onclick="showPavilionModal()">
                        <i class="bi bi-plus-circle"></i> Pavilion Nou
                    </button>
                </div>
                <div id="pavilions-table-container">
                    <p class="text-center text-muted">Se încarcă...</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Container Types Tab (Admin only) -->
        <?php if ($currentUser['is_admin']): ?>
        <div id="container-types-tab" class="tab-pane-content" style="display: none;">
            <div class="tab-content-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5>Gestionare Tipuri Containere</h5>
                    <button class="btn btn-primary" onclick="showContainerTypeModal()">
                        <i class="bi bi-plus-circle"></i> Tip Nou
                    </button>
                </div>
                <div id="container-types-table-container">
                    <p class="text-center text-muted">Se încarcă...</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Import Templates Tab (Admin only) -->
        <?php if ($currentUser['is_admin']): ?>
        <div id="import-templates-tab" class="tab-pane-content" style="display: none;">
            <div class="tab-content-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5>Gestionare Template-uri Import</h5>
                    <button class="btn btn-primary" onclick="showTemplateModal()">
                        <i class="bi bi-plus-circle"></i> Template Nou
                    </button>
                </div>
                <div id="templates-table-container">
                    <p class="text-center text-muted">Se încarcă...</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Email Settings Tab (Admin only) -->
        <?php if ($currentUser['is_admin']): ?>
        <div id="email-settings-tab" class="tab-pane-content" style="display: none;">
            <div class="tab-content-section">
                <h5 class="mb-4">Setări Email SMTP</h5>
                <div id="email-settings-container">
                    <p class="text-center text-muted">Se încarcă...</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Import Excel Tab -->
        <div id="import-excel-tab" class="tab-pane-content" style="display: none;">
            <div class="tab-content-section">
                <h5 class="mb-4">Import Excel</h5>
                <div id="import-form-container">
                    <p class="text-center text-muted">Se încarcă...</p>
                </div>
            </div>
        </div>

        <!-- Manifests View Tab -->
        <!-- Export Data Tab -->
        <div id="export-data-tab" class="tab-pane-content" style="display: none;">
            <div class="tab-content-section">
                <h5 class="mb-4">Export Date</h5>
                <div id="export-options-container">
                    <p class="text-center text-muted">Se încarcă...</p>
                </div>
            </div>
        </div>

        <!-- Import Logs Tab -->
        <div id="import-logs-tab" class="tab-pane-content" style="display: none;">
            <div class="tab-content-section">
                <h5 class="mb-3">Istoric Import</h5>
                <div id="logs-table-container">
                    <p class="text-center text-muted">Se încarcă...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Utilizator -->
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userModalTitle">Utilizator Nou</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="userForm">
                        <input type="hidden" id="userId" name="id">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username *</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Parolă <span id="passwordHint">(obligatorie pentru utilizator nou)</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="password" name="password" placeholder="Introdu sau generează parolă">
                                <button class="btn btn-outline-secondary" type="button" onclick="generatePassword()">
                                    <i class="bi bi-key"></i> Generează
                                </button>
                            </div>
                            <small class="text-muted" id="generatedPasswordInfo" style="display: none;">
                                Parolă generată: <code id="generatedPasswordDisplay"></code>
                            </small>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" required placeholder="Obligatoriu pentru trimitere credențiale">
                        </div>
                        <div class="mb-3">
                            <label for="fullName" class="form-label">Nume Complet</label>
                            <input type="text" class="form-control" id="fullName" name="full_name">
                        </div>
                        <div class="mb-3">
                            <label for="companyName" class="form-label">Companie</label>
                            <input type="text" class="form-control" id="companyName" name="company_name">
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="isActive" name="is_active" checked>
                                    <label class="form-check-label" for="isActive">Activ</label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="isAdmin" name="is_admin">
                                    <label class="form-check-label" for="isAdmin">Administrator</label>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="sendCredentials" name="send_credentials">
                            <label class="form-check-label" for="sendCredentials">
                                <i class="bi bi-envelope"></i> Trimite credențiale prin email după salvare
                            </label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="button" class="btn btn-primary" onclick="saveUser()">Salvează</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal An Baza Date -->
    <div class="modal fade" id="yearModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="yearModalTitle">An Nou</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="yearForm">
                        <input type="hidden" id="yearId" name="id">
                        <div class="mb-3">
                            <label for="yearValue" class="form-label">Anul *</label>
                            <input type="number" class="form-control" id="yearValue" name="year" min="2000" max="2100" required>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="yearIsActive" name="is_active">
                            <label class="form-check-label" for="yearIsActive">An Activ</label>
                            <small class="form-text text-muted d-block">Doar un an poate fi activ la un moment dat</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="button" class="btn btn-primary" onclick="saveYear()">Salvează</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Pavilion -->
    <div class="modal fade" id="pavilionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pavilionModalTitle">Pavilion Nou</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="pavilionForm">
                        <input type="hidden" id="pavilionId" name="id">
                        <div class="mb-3">
                            <label for="pavilionName" class="form-label">Nume Pavilion *</label>
                            <input type="text" class="form-control" id="pavilionName" name="name" required placeholder="ex: LIBERIA">
                        </div>
                        <div class="mb-3">
                            <label for="pavilionCountry" class="form-label">Nume Țară</label>
                            <input type="text" class="form-control" id="pavilionCountry" name="country_name" placeholder="ex: Republica Liberia">
                        </div>
                        <div class="mb-3">
                            <label for="pavilionImage" class="form-label">URL Imagine Steag</label>
                            <input type="text" class="form-control" id="pavilionImage" name="flag_image" placeholder="ex: /uploads/pavilions/liberia.png">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="button" class="btn btn-primary" onclick="savePavilion()">Salvează</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tip Container -->
    <div class="modal fade" id="containerTypeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="containerTypeModalTitle">Tip Container Nou</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="containerTypeForm">
                        <input type="hidden" id="containerTypeId" name="id">
                        <div class="mb-3">
                            <label for="containerModelCode" class="form-label">Model Container *</label>
                            <input type="text" class="form-control" id="containerModelCode" name="model_code" required placeholder="ex: SUDU22G1">
                        </div>
                        <div class="mb-3">
                            <label for="containerTypeCode" class="form-label">Tip Container</label>
                            <input type="text" class="form-control" id="containerTypeCode" name="type_code" placeholder="ex: 22G1">
                        </div>
                        <div class="mb-3">
                            <label for="containerPrefix" class="form-label">Prefix</label>
                            <input type="text" class="form-control" id="containerPrefix" name="prefix" placeholder="ex: SUDU">
                        </div>
                        <div class="mb-3">
                            <label for="containerDescription" class="form-label">Descriere</label>
                            <textarea class="form-control" id="containerDescription" name="description" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="containerImage" class="form-label">URL Imagine</label>
                            <input type="text" class="form-control" id="containerImage" name="image" placeholder="ex: /uploads/containers/22g1.jpg">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="button" class="btn btn-primary" onclick="saveContainerType()">Salvează</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Template Import -->
    <div class="modal fade" id="templateModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="templateModalTitle">Template Nou</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="templateForm">
                        <input type="hidden" id="templateId" name="id">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="templateName" class="form-label">Nume Template *</label>
                                <input type="text" class="form-control" id="templateName" name="name" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="templateFormat" class="form-label">Format Fișier</label>
                                <select class="form-select" id="templateFormat" name="file_format">
                                    <option value="xlsx">XLSX</option>
                                    <option value="xls">XLS</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="templateStartRow" class="form-label">Rând Start</label>
                                <input type="number" class="form-control" id="templateStartRow" name="start_row" value="2" min="1">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="templateDescription" class="form-label">Descriere</label>
                            <textarea class="form-control" id="templateDescription" name="description" rows="2"></textarea>
                        </div>
                        <hr>
                        <h6>Mapare Coloane (litera coloanei Excel: A, B, C...)</h6>
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <label class="form-label small">Număr Poziție</label>
                                <input type="text" class="form-control form-control-sm" id="mapNumarPozitie" placeholder="ex: A">
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="form-label small">Container</label>
                                <input type="text" class="form-control form-control-sm" id="mapContainer" placeholder="ex: B">
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="form-label small">Tip Container</label>
                                <input type="text" class="form-control form-control-sm" id="mapTipContainer" placeholder="ex: C">
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="form-label small">Număr Colete</label>
                                <input type="text" class="form-control form-control-sm" id="mapNumarColete" placeholder="ex: D">
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="form-label small">Greutate Brută</label>
                                <input type="text" class="form-control form-control-sm" id="mapGreutateBruta" placeholder="ex: E">
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="form-label small">Descriere Marfă</label>
                                <input type="text" class="form-control form-control-sm" id="mapDescriereMarfa" placeholder="ex: F">
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="form-label small">Tip Operațiune</label>
                                <input type="text" class="form-control form-control-sm" id="mapTipOperatiune" placeholder="ex: G">
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="form-label small">Număr Sumară</label>
                                <input type="text" class="form-control form-control-sm" id="mapNumarSumara" placeholder="ex: H">
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="form-label small">Linie Maritimă</label>
                                <input type="text" class="form-control form-control-sm" id="mapLinieMaritima" placeholder="ex: I">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
                    <button type="button" class="btn btn-primary" onclick="saveTemplate()">Salvează</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/import-excel.js?v=20250130b"></script>
    <script src="assets/js/manifest-management.js?v=20250130b"></script>
    <script>
        // Tab switching
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-pane-content').forEach(tab => {
                tab.style.display = 'none';
            });

            // Remove active class from all links
            document.querySelectorAll('.sidebar-nav-link').forEach(link => {
                link.classList.remove('active');
            });

            // Show selected tab
            const selectedTab = document.getElementById(tabName + '-tab');
            if (selectedTab) {
                selectedTab.style.display = 'block';
            }

            // Add active class to clicked link
            const activeLink = document.querySelector(`[data-tab="${tabName}"]`);
            if (activeLink) {
                activeLink.classList.add('active');
            }

            // Load tab content
            loadTabContent(tabName);
        }

        // Add click handlers to sidebar links
        document.querySelectorAll('.sidebar-nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const tabName = this.getAttribute('data-tab');
                switchTab(tabName);
            });
        });

        // Load tab content dynamically
        function loadTabContent(tabName) {
            switch(tabName) {
                case 'manifests':
                    loadManifestsView();
                    break;
                case 'users':
                    loadUsers();
                    break;
                case 'database-years':
                    loadDatabaseYears();
                    break;
                case 'pavilions':
                    loadPavilions();
                    break;
                case 'container-types':
                    loadContainerTypes();
                    break;
                case 'import-templates':
                    loadImportTemplates();
                    break;
                case 'email-settings':
                    loadEmailSettings();
                    break;
                case 'import-excel':
                    loadImportForm();
                    break;
                case 'export-data':
                    loadExportOptions();
                    break;
                case 'import-logs':
                    loadImportLogs();
                    break;
            }
        }

        // Users management
        let usersCurrentPage = 1;

        async function loadUsers(page = 1) {
            usersCurrentPage = page;
            const container = document.getElementById('users-table-container');
            container.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';

            try {
                const response = await fetch(`api/users.php?page=${page}`);
                const result = await response.json();

                if (result.error) {
                    container.innerHTML = `<div class="alert alert-danger">${result.error}</div>`;
                    return;
                }

                const users = result.data || [];
                const pagination = result.pagination || {};

                if (users.length === 0) {
                    container.innerHTML = '<div class="alert alert-info">Nu există utilizatori înregistrați.</div>';
                    return;
                }

                let html = `
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Nume Complet</th>
                                    <th>Companie</th>
                                    <th>Status</th>
                                    <th>Rol</th>
                                    <th>Ultima Logare</th>
                                    <th>Acțiuni</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                users.forEach(user => {
                    const statusBadge = user.is_active
                        ? '<span class="badge bg-success">Activ</span>'
                        : '<span class="badge bg-danger">Inactiv</span>';
                    const roleBadge = user.is_admin
                        ? '<span class="badge bg-warning text-dark">Admin</span>'
                        : '<span class="badge bg-secondary">Utilizator</span>';
                    const lastLogin = user.last_login ? formatDateTime(user.last_login) : 'Niciodată';

                    html += `
                        <tr>
                            <td>${user.id}</td>
                            <td><strong>${escapeHtml(user.username)}</strong></td>
                            <td>${escapeHtml(user.email || '-')}</td>
                            <td>${escapeHtml(user.full_name || '-')}</td>
                            <td>${escapeHtml(user.company_name || '-')}</td>
                            <td>${statusBadge}</td>
                            <td>${roleBadge}</td>
                            <td>${lastLogin}</td>
                            <td class="table-actions">
                                <button class="btn btn-sm btn-outline-primary" onclick="editUser(${user.id})" title="Editează">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteUser(${user.id}, '${escapeHtml(user.username)}')" title="Șterge">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });

                html += '</tbody></table></div>';

                // Paginare
                if (pagination.total_pages > 1) {
                    html += '<nav><ul class="pagination justify-content-center">';
                    html += `<li class="page-item ${usersCurrentPage === 1 ? 'disabled' : ''}">
                        <a class="page-link" href="#" onclick="loadUsers(${usersCurrentPage - 1}); return false;">«</a>
                    </li>`;

                    for (let i = 1; i <= pagination.total_pages; i++) {
                        if (i === 1 || i === pagination.total_pages || Math.abs(i - usersCurrentPage) <= 2) {
                            html += `<li class="page-item ${i === usersCurrentPage ? 'active' : ''}">
                                <a class="page-link" href="#" onclick="loadUsers(${i}); return false;">${i}</a>
                            </li>`;
                        } else if (Math.abs(i - usersCurrentPage) === 3) {
                            html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                    }

                    html += `<li class="page-item ${usersCurrentPage === pagination.total_pages ? 'disabled' : ''}">
                        <a class="page-link" href="#" onclick="loadUsers(${usersCurrentPage + 1}); return false;">»</a>
                    </li>`;
                    html += '</ul></nav>';
                }

                container.innerHTML = html;

            } catch (error) {
                container.innerHTML = `<div class="alert alert-danger">Eroare: ${error.message}</div>`;
            }
        }

        function showUserModal(userId = null) {
            const modal = new bootstrap.Modal(document.getElementById('userModal'));
            const form = document.getElementById('userForm');
            const title = document.getElementById('userModalTitle');
            const passwordHint = document.getElementById('passwordHint');

            // Reset form
            form.reset();
            document.getElementById('userId').value = '';
            document.getElementById('isActive').checked = true;
            document.getElementById('isAdmin').checked = false;
            document.getElementById('sendCredentials').checked = false;
            document.getElementById('generatedPasswordInfo').style.display = 'none';

            if (userId) {
                title.textContent = 'Editare Utilizator';
                passwordHint.textContent = '(lasă gol pentru a păstra parola curentă)';
                document.getElementById('password').required = false;
                document.getElementById('email').required = false;
            } else {
                title.textContent = 'Utilizator Nou';
                passwordHint.textContent = '(obligatorie pentru utilizator nou)';
                document.getElementById('password').required = true;
                document.getElementById('email').required = true;
            }

            modal.show();
        }

        // Generare parolă aleatorie
        function generatePassword() {
            const length = 12;
            const uppercase = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
            const lowercase = 'abcdefghjkmnpqrstuvwxyz';
            const numbers = '23456789';
            const special = '!@#$%&*';
            const allChars = uppercase + lowercase + numbers + special;

            let password = '';
            // Asigură cel puțin un caracter din fiecare categorie
            password += uppercase[Math.floor(Math.random() * uppercase.length)];
            password += lowercase[Math.floor(Math.random() * lowercase.length)];
            password += numbers[Math.floor(Math.random() * numbers.length)];
            password += special[Math.floor(Math.random() * special.length)];

            // Completează restul
            for (let i = password.length; i < length; i++) {
                password += allChars[Math.floor(Math.random() * allChars.length)];
            }

            // Amestecă caracterele
            password = password.split('').sort(() => Math.random() - 0.5).join('');

            document.getElementById('password').value = password;
            document.getElementById('generatedPasswordDisplay').textContent = password;
            document.getElementById('generatedPasswordInfo').style.display = 'block';

            // Salvează parola generată pentru trimitere email
            window.lastGeneratedPassword = password;
        }

        async function editUser(userId) {
            try {
                const response = await fetch(`api/users.php?id=${userId}`);
                const user = await response.json();

                if (user.error) {
                    alert('Eroare: ' + user.error);
                    return;
                }

                // Populează formularul
                document.getElementById('userId').value = user.id;
                document.getElementById('username').value = user.username || '';
                document.getElementById('email').value = user.email || '';
                document.getElementById('fullName').value = user.full_name || '';
                document.getElementById('companyName').value = user.company_name || '';
                document.getElementById('isActive').checked = user.is_active == 1;
                document.getElementById('isAdmin').checked = user.is_admin == 1;
                document.getElementById('password').value = '';

                showUserModal(userId);

            } catch (error) {
                alert('Eroare la încărcarea utilizatorului: ' + error.message);
            }
        }

        async function saveUser() {
            const userId = document.getElementById('userId').value;
            const isNew = !userId;

            const data = {
                username: document.getElementById('username').value.trim(),
                email: document.getElementById('email').value.trim(),
                full_name: document.getElementById('fullName').value.trim(),
                company_name: document.getElementById('companyName').value.trim(),
                is_active: document.getElementById('isActive').checked ? 1 : 0,
                is_admin: document.getElementById('isAdmin').checked ? 1 : 0
            };

            const password = document.getElementById('password').value;
            const sendCredentials = document.getElementById('sendCredentials').checked;

            if (password) {
                data.password = password;
            } else if (isNew) {
                alert('Parola este obligatorie pentru utilizator nou!');
                return;
            }

            if (!data.username) {
                alert('Username-ul este obligatoriu!');
                return;
            }

            // Email obligatoriu la creare
            if (isNew && !data.email) {
                alert('Email-ul este obligatoriu pentru utilizator nou!');
                return;
            }

            // Verifică dacă trimitem email dar nu avem parolă
            if (sendCredentials && !password) {
                alert('Pentru a trimite credențiale prin email, trebuie să setezi o parolă!');
                return;
            }

            if (sendCredentials && !data.email) {
                alert('Pentru a trimite credențiale prin email, trebuie să setezi adresa de email!');
                return;
            }

            if (userId) {
                data.id = userId;
            }

            try {
                const response = await fetch('api/users.php', {
                    method: isNew ? 'POST' : 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.error) {
                    alert('Eroare: ' + result.error);
                    return;
                }

                // Dacă trebuie să trimitem credențiale
                if (sendCredentials && password) {
                    const newUserId = result.id || userId;
                    await sendCredentialsEmail(newUserId, password);
                }

                // Închide modalul
                bootstrap.Modal.getInstance(document.getElementById('userModal')).hide();

                // Reîncarcă lista
                loadUsers(usersCurrentPage);

                let message = isNew ? 'Utilizator creat cu succes!' : 'Utilizator actualizat cu succes!';
                if (sendCredentials) {
                    message += '\n\nCredențialele au fost trimise la ' + data.email;
                }
                alert(message);

            } catch (error) {
                alert('Eroare: ' + error.message);
            }
        }

        // Trimite credențiale prin email
        async function sendCredentialsEmail(userId, password) {
            try {
                const response = await fetch('api/send_credentials.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: userId, password: password })
                });

                const result = await response.json();

                if (result.error) {
                    alert('Atenție: Utilizatorul a fost salvat, dar email-ul nu a putut fi trimis.\n\nEroare: ' + result.error);
                }

            } catch (error) {
                alert('Atenție: Utilizatorul a fost salvat, dar a apărut o eroare la trimiterea email-ului.');
            }
        }

        async function deleteUser(userId, username) {
            if (!confirm(`Sigur ștergi utilizatorul "${username}"?\n\nAceastă acțiune nu poate fi anulată!`)) {
                return;
            }

            try {
                const response = await fetch(`api/users.php?id=${userId}`, {
                    method: 'DELETE'
                });

                const result = await response.json();

                if (result.error) {
                    alert('Eroare: ' + result.error);
                    return;
                }

                loadUsers(usersCurrentPage);
                alert('Utilizator șters cu succes!');

            } catch (error) {
                alert('Eroare: ' + error.message);
            }
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatDateTime(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleDateString('ro-RO') + ' ' + date.toLocaleTimeString('ro-RO', {hour: '2-digit', minute: '2-digit'});
        }

        // =============================================
        // ANI BAZE DATE MANAGEMENT
        // =============================================
        async function loadDatabaseYears() {
            const container = document.getElementById('years-table-container');
            container.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';

            try {
                const response = await fetch('api/database_years.php');
                const result = await response.json();

                if (result.error) {
                    container.innerHTML = `<div class="alert alert-danger">${result.error}</div>`;
                    return;
                }

                const years = result.data || [];

                if (years.length === 0) {
                    container.innerHTML = '<div class="alert alert-info">Nu există ani înregistrați. Adaugă primul an!</div>';
                    return;
                }

                let html = `
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>An</th>
                                    <th>Status</th>
                                    <th>Data Creare</th>
                                    <th>Acțiuni</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                years.forEach(year => {
                    const statusBadge = year.is_active == 1
                        ? '<span class="badge bg-success">Activ</span>'
                        : '<span class="badge bg-secondary">Inactiv</span>';
                    const createdAt = year.created_at ? formatDateTime(year.created_at) : '-';

                    html += `
                        <tr>
                            <td>${year.id}</td>
                            <td><strong>Registru ${year.year}</strong></td>
                            <td>${statusBadge}</td>
                            <td>${createdAt}</td>
                            <td class="table-actions">
                                <button class="btn btn-sm btn-outline-success" onclick="activateYear(${year.id})" title="Activează">
                                    <i class="bi bi-check-circle"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteYear(${year.id}, ${year.year})" title="Șterge">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });

                html += '</tbody></table></div>';
                container.innerHTML = html;

            } catch (error) {
                container.innerHTML = `<div class="alert alert-danger">Eroare: ${error.message}</div>`;
            }
        }

        function showYearModal() {
            const modal = new bootstrap.Modal(document.getElementById('yearModal'));
            document.getElementById('yearForm').reset();
            document.getElementById('yearId').value = '';
            document.getElementById('yearValue').value = new Date().getFullYear();
            document.getElementById('yearModalTitle').textContent = 'An Nou';
            modal.show();
        }

        async function saveYear() {
            const yearId = document.getElementById('yearId').value;
            const isNew = !yearId;

            const data = {
                year: parseInt(document.getElementById('yearValue').value),
                is_active: document.getElementById('yearIsActive').checked ? 1 : 0
            };

            if (yearId) data.id = yearId;

            try {
                const response = await fetch('api/database_years.php', {
                    method: isNew ? 'POST' : 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.error) {
                    alert('Eroare: ' + result.error);
                    return;
                }

                bootstrap.Modal.getInstance(document.getElementById('yearModal')).hide();
                loadDatabaseYears();
                alert(isNew ? 'An creat cu succes!' : 'An actualizat cu succes!');

            } catch (error) {
                alert('Eroare: ' + error.message);
            }
        }

        async function activateYear(yearId) {
            if (!confirm('Activezi acest an? Toate celelalte ani vor fi dezactivați.')) return;

            try {
                const response = await fetch('api/database_years.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: yearId, is_active: 1 })
                });

                const result = await response.json();
                if (result.error) {
                    alert('Eroare: ' + result.error);
                    return;
                }

                loadDatabaseYears();
                alert('An activat cu succes!');

            } catch (error) {
                alert('Eroare: ' + error.message);
            }
        }

        async function deleteYear(yearId, yearValue) {
            if (!confirm(`Sigur ștergi anul ${yearValue}?`)) return;

            try {
                const response = await fetch(`api/database_years.php?id=${yearId}`, { method: 'DELETE' });
                const result = await response.json();

                if (result.error) {
                    alert('Eroare: ' + result.error);
                    return;
                }

                loadDatabaseYears();
                alert('An șters cu succes!');

            } catch (error) {
                alert('Eroare: ' + error.message);
            }
        }

        // =============================================
        // PAVILIOANE MANAGEMENT
        // =============================================
        async function loadPavilions() {
            const container = document.getElementById('pavilions-table-container');
            container.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';

            try {
                const response = await fetch('api/pavilions.php');
                const result = await response.json();

                if (result.error) {
                    container.innerHTML = `<div class="alert alert-danger">${result.error}</div>`;
                    return;
                }

                const pavilions = result.data || [];

                if (pavilions.length === 0) {
                    container.innerHTML = '<div class="alert alert-info">Nu există pavilioane înregistrate.</div>';
                    return;
                }

                let html = `
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Steag</th>
                                    <th>Nume Pavilion</th>
                                    <th>Nume Țară</th>
                                    <th>Nr. Nave</th>
                                    <th>Acțiuni</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                pavilions.forEach(p => {
                    const flagImg = p.flag_image
                        ? `<img src="${escapeHtml(p.flag_image)}" width="40" height="25" style="object-fit: contain;">`
                        : '<span class="text-muted">-</span>';

                    html += `
                        <tr>
                            <td>${p.id}</td>
                            <td>${flagImg}</td>
                            <td><strong>${escapeHtml(p.name)}</strong></td>
                            <td>${escapeHtml(p.country_name || '-')}</td>
                            <td><span class="badge bg-info">${p.ships_count || 0}</span></td>
                            <td class="table-actions">
                                <button class="btn btn-sm btn-outline-primary" onclick="editPavilion(${p.id})" title="Editează">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deletePavilion(${p.id}, '${escapeHtml(p.name)}')" title="Șterge">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });

                html += '</tbody></table></div>';
                container.innerHTML = html;

            } catch (error) {
                container.innerHTML = `<div class="alert alert-danger">Eroare: ${error.message}</div>`;
            }
        }

        function showPavilionModal() {
            const modal = new bootstrap.Modal(document.getElementById('pavilionModal'));
            document.getElementById('pavilionForm').reset();
            document.getElementById('pavilionId').value = '';
            document.getElementById('pavilionModalTitle').textContent = 'Pavilion Nou';
            modal.show();
        }

        async function editPavilion(pavilionId) {
            try {
                const response = await fetch(`api/pavilions.php?id=${pavilionId}`);
                const p = await response.json();

                if (p.error) {
                    alert('Eroare: ' + p.error);
                    return;
                }

                document.getElementById('pavilionId').value = p.id;
                document.getElementById('pavilionName').value = p.name || '';
                document.getElementById('pavilionCountry').value = p.country_name || '';
                document.getElementById('pavilionImage').value = p.flag_image || '';
                document.getElementById('pavilionModalTitle').textContent = 'Editare Pavilion';

                const modal = new bootstrap.Modal(document.getElementById('pavilionModal'));
                modal.show();

            } catch (error) {
                alert('Eroare: ' + error.message);
            }
        }

        async function savePavilion() {
            const pavilionId = document.getElementById('pavilionId').value;
            const isNew = !pavilionId;

            const data = {
                name: document.getElementById('pavilionName').value.trim(),
                country_name: document.getElementById('pavilionCountry').value.trim(),
                flag_image: document.getElementById('pavilionImage').value.trim()
            };

            if (!data.name) {
                alert('Numele pavilionului este obligatoriu!');
                return;
            }

            if (pavilionId) data.id = pavilionId;

            try {
                const response = await fetch('api/pavilions.php', {
                    method: isNew ? 'POST' : 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.error) {
                    alert('Eroare: ' + result.error);
                    return;
                }

                bootstrap.Modal.getInstance(document.getElementById('pavilionModal')).hide();
                loadPavilions();
                alert(isNew ? 'Pavilion creat cu succes!' : 'Pavilion actualizat cu succes!');

            } catch (error) {
                alert('Eroare: ' + error.message);
            }
        }

        async function deletePavilion(pavilionId, name) {
            if (!confirm(`Sigur ștergi pavilionul "${name}"?`)) return;

            try {
                const response = await fetch(`api/pavilions.php?id=${pavilionId}`, { method: 'DELETE' });
                const result = await response.json();

                if (result.error) {
                    alert('Eroare: ' + result.error);
                    return;
                }

                loadPavilions();
                alert('Pavilion șters cu succes!');

            } catch (error) {
                alert('Eroare: ' + error.message);
            }
        }

        // =============================================
        // TIPURI CONTAINERE MANAGEMENT
        // =============================================
        async function loadContainerTypes() {
            const container = document.getElementById('container-types-table-container');
            container.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';

            try {
                const response = await fetch('api/container_types.php');
                const result = await response.json();

                if (result.error) {
                    container.innerHTML = `<div class="alert alert-danger">${result.error}</div>`;
                    return;
                }

                const types = result.data || [];

                if (types.length === 0) {
                    container.innerHTML = '<div class="alert alert-info">Nu există tipuri de containere înregistrate.</div>';
                    return;
                }

                let html = `
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Imagine</th>
                                    <th>Model Container</th>
                                    <th>Tip Container</th>
                                    <th>Prefix</th>
                                    <th>Nr. Înregistrări</th>
                                    <th>Acțiuni</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                types.forEach(t => {
                    const img = t.image
                        ? `<img src="${escapeHtml(t.image)}" width="60" height="40" style="object-fit: cover; border-radius: 4px;">`
                        : '<span class="text-muted">-</span>';

                    html += `
                        <tr>
                            <td>${t.id}</td>
                            <td>${img}</td>
                            <td><strong>${escapeHtml(t.model_code)}</strong></td>
                            <td>${escapeHtml(t.type_code || '-')}</td>
                            <td>${escapeHtml(t.prefix || '-')}</td>
                            <td><span class="badge bg-info">${t.entries_count || 0}</span></td>
                            <td class="table-actions">
                                <button class="btn btn-sm btn-outline-primary" onclick="editContainerType(${t.id})" title="Editează">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteContainerType(${t.id}, '${escapeHtml(t.model_code)}')" title="Șterge">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });

                html += '</tbody></table></div>';
                container.innerHTML = html;

            } catch (error) {
                container.innerHTML = `<div class="alert alert-danger">Eroare: ${error.message}</div>`;
            }
        }

        function showContainerTypeModal() {
            const modal = new bootstrap.Modal(document.getElementById('containerTypeModal'));
            document.getElementById('containerTypeForm').reset();
            document.getElementById('containerTypeId').value = '';
            document.getElementById('containerTypeModalTitle').textContent = 'Tip Container Nou';
            modal.show();
        }

        async function editContainerType(typeId) {
            try {
                const response = await fetch(`api/container_types.php?id=${typeId}`);
                const t = await response.json();

                if (t.error) {
                    alert('Eroare: ' + t.error);
                    return;
                }

                document.getElementById('containerTypeId').value = t.id;
                document.getElementById('containerModelCode').value = t.model_code || '';
                document.getElementById('containerTypeCode').value = t.type_code || '';
                document.getElementById('containerPrefix').value = t.prefix || '';
                document.getElementById('containerDescription').value = t.description || '';
                document.getElementById('containerImage').value = t.image || '';
                document.getElementById('containerTypeModalTitle').textContent = 'Editare Tip Container';

                const modal = new bootstrap.Modal(document.getElementById('containerTypeModal'));
                modal.show();

            } catch (error) {
                alert('Eroare: ' + error.message);
            }
        }

        async function saveContainerType() {
            const typeId = document.getElementById('containerTypeId').value;
            const isNew = !typeId;

            const data = {
                model_code: document.getElementById('containerModelCode').value.trim(),
                type_code: document.getElementById('containerTypeCode').value.trim(),
                prefix: document.getElementById('containerPrefix').value.trim(),
                description: document.getElementById('containerDescription').value.trim(),
                image: document.getElementById('containerImage').value.trim()
            };

            if (!data.model_code) {
                alert('Model container este obligatoriu!');
                return;
            }

            if (typeId) data.id = typeId;

            try {
                const response = await fetch('api/container_types.php', {
                    method: isNew ? 'POST' : 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.error) {
                    alert('Eroare: ' + result.error);
                    return;
                }

                bootstrap.Modal.getInstance(document.getElementById('containerTypeModal')).hide();
                loadContainerTypes();
                alert(isNew ? 'Tip container creat cu succes!' : 'Tip container actualizat cu succes!');

            } catch (error) {
                alert('Eroare: ' + error.message);
            }
        }

        async function deleteContainerType(typeId, modelCode) {
            if (!confirm(`Sigur ștergi tipul de container "${modelCode}"?`)) return;

            try {
                const response = await fetch(`api/container_types.php?id=${typeId}`, { method: 'DELETE' });
                const result = await response.json();

                if (result.error) {
                    alert('Eroare: ' + result.error);
                    return;
                }

                loadContainerTypes();
                alert('Tip container șters cu succes!');

            } catch (error) {
                alert('Eroare: ' + error.message);
            }
        }

        // =============================================
        // TEMPLATE-URI IMPORT MANAGEMENT
        // =============================================
        async function loadImportTemplates() {
            const container = document.getElementById('templates-table-container');
            container.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';

            try {
                const response = await fetch('api/import_templates.php');
                const result = await response.json();

                if (result.error) {
                    container.innerHTML = `<div class="alert alert-danger">${result.error}</div>`;
                    return;
                }

                const templates = result.data || [];

                if (templates.length === 0) {
                    container.innerHTML = '<div class="alert alert-info">Nu există template-uri de import. Creează primul template!</div>';
                    return;
                }

                let html = `
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Nume Template</th>
                                    <th>Format</th>
                                    <th>Rând Start</th>
                                    <th>Coloane Mapate</th>
                                    <th>Data Creare</th>
                                    <th>Acțiuni</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                templates.forEach(t => {
                    const mapping = t.column_mapping || {};
                    const mappedCount = Object.keys(mapping).filter(k => mapping[k]).length;
                    const createdAt = t.created_at ? formatDateTime(t.created_at) : '-';

                    html += `
                        <tr>
                            <td>${t.id}</td>
                            <td><strong>${escapeHtml(t.name)}</strong></td>
                            <td><span class="badge bg-secondary">${escapeHtml(t.file_format || 'xlsx').toUpperCase()}</span></td>
                            <td>${t.start_row || 2}</td>
                            <td><span class="badge bg-info">${mappedCount} coloane</span></td>
                            <td>${createdAt}</td>
                            <td class="table-actions">
                                <button class="btn btn-sm btn-outline-primary" onclick="editTemplate(${t.id})" title="Editează">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteTemplate(${t.id}, '${escapeHtml(t.name)}')" title="Șterge">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });

                html += '</tbody></table></div>';
                container.innerHTML = html;

            } catch (error) {
                container.innerHTML = `<div class="alert alert-danger">Eroare: ${error.message}</div>`;
            }
        }

        function showTemplateModal() {
            const modal = new bootstrap.Modal(document.getElementById('templateModal'));
            document.getElementById('templateForm').reset();
            document.getElementById('templateId').value = '';
            document.getElementById('templateModalTitle').textContent = 'Template Nou';
            // Reset all mapping fields
            ['mapNumarPozitie', 'mapContainer', 'mapTipContainer', 'mapNumarColete', 'mapGreutateBruta',
             'mapDescriereMarfa', 'mapTipOperatiune', 'mapNumarSumara', 'mapLinieMaritima'].forEach(id => {
                document.getElementById(id).value = '';
            });
            modal.show();
        }

        async function editTemplate(templateId) {
            try {
                const response = await fetch(`api/import_templates.php?id=${templateId}`);
                const t = await response.json();

                if (t.error) {
                    alert('Eroare: ' + t.error);
                    return;
                }

                document.getElementById('templateId').value = t.id;
                document.getElementById('templateName').value = t.name || '';
                document.getElementById('templateFormat').value = t.file_format || 'xlsx';
                document.getElementById('templateStartRow').value = t.start_row || 2;
                document.getElementById('templateDescription').value = t.description || '';
                document.getElementById('templateModalTitle').textContent = 'Editare Template';

                // Populate column mapping
                const mapping = t.column_mapping || {};
                document.getElementById('mapNumarPozitie').value = mapping.numar_pozitie || '';
                document.getElementById('mapContainer').value = mapping.container || '';
                document.getElementById('mapTipContainer').value = mapping.tip_container || '';
                document.getElementById('mapNumarColete').value = mapping.numar_colete || '';
                document.getElementById('mapGreutateBruta').value = mapping.greutate_bruta || '';
                document.getElementById('mapDescriereMarfa').value = mapping.descriere_marfa || '';
                document.getElementById('mapTipOperatiune').value = mapping.tip_operatiune || '';
                document.getElementById('mapNumarSumara').value = mapping.numar_sumara || '';
                document.getElementById('mapLinieMaritima').value = mapping.linie_maritima || '';

                const modal = new bootstrap.Modal(document.getElementById('templateModal'));
                modal.show();

            } catch (error) {
                alert('Eroare: ' + error.message);
            }
        }

        async function saveTemplate() {
            const templateId = document.getElementById('templateId').value;
            const isNew = !templateId;

            const columnMapping = {
                numar_pozitie: document.getElementById('mapNumarPozitie').value.trim().toUpperCase(),
                container: document.getElementById('mapContainer').value.trim().toUpperCase(),
                tip_container: document.getElementById('mapTipContainer').value.trim().toUpperCase(),
                numar_colete: document.getElementById('mapNumarColete').value.trim().toUpperCase(),
                greutate_bruta: document.getElementById('mapGreutateBruta').value.trim().toUpperCase(),
                descriere_marfa: document.getElementById('mapDescriereMarfa').value.trim().toUpperCase(),
                tip_operatiune: document.getElementById('mapTipOperatiune').value.trim().toUpperCase(),
                numar_sumara: document.getElementById('mapNumarSumara').value.trim().toUpperCase(),
                linie_maritima: document.getElementById('mapLinieMaritima').value.trim().toUpperCase()
            };

            // Remove empty mappings
            Object.keys(columnMapping).forEach(key => {
                if (!columnMapping[key]) delete columnMapping[key];
            });

            const data = {
                name: document.getElementById('templateName').value.trim(),
                file_format: document.getElementById('templateFormat').value,
                start_row: parseInt(document.getElementById('templateStartRow').value) || 2,
                description: document.getElementById('templateDescription').value.trim(),
                column_mapping: columnMapping
            };

            if (!data.name) {
                alert('Numele template-ului este obligatoriu!');
                return;
            }

            if (templateId) data.id = templateId;

            try {
                const response = await fetch('api/import_templates.php', {
                    method: isNew ? 'POST' : 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.error) {
                    alert('Eroare: ' + result.error);
                    return;
                }

                bootstrap.Modal.getInstance(document.getElementById('templateModal')).hide();
                loadImportTemplates();
                alert(isNew ? 'Template creat cu succes!' : 'Template actualizat cu succes!');

            } catch (error) {
                alert('Eroare: ' + error.message);
            }
        }

        async function deleteTemplate(templateId, name) {
            if (!confirm(`Sigur ștergi template-ul "${name}"?`)) return;

            try {
                const response = await fetch(`api/import_templates.php?id=${templateId}`, { method: 'DELETE' });
                const result = await response.json();

                if (result.error) {
                    alert('Eroare: ' + result.error);
                    return;
                }

                loadImportTemplates();
                alert('Template șters cu succes!');

            } catch (error) {
                alert('Eroare: ' + error.message);
            }
        }

        // =============================================
        // SETĂRI EMAIL
        // =============================================
        async function loadEmailSettings() {
            const container = document.getElementById('email-settings-container');
            container.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';

            try {
                const response = await fetch('api/email_settings.php');
                const settings = await response.json();

                if (settings.error) {
                    container.innerHTML = `<div class="alert alert-danger">${settings.error}</div>`;
                    return;
                }

                container.innerHTML = `
                    <form id="emailSettingsForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h6 class="mb-0">Server SMTP</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">Server SMTP *</label>
                                            <input type="text" class="form-control" id="smtpHost" value="${escapeHtml(settings.smtp_host || '')}" placeholder="ex: mail.lentiu.ro" required>
                                        </div>
                                        <div class="row">
                                            <div class="col-6 mb-3">
                                                <label class="form-label">Port *</label>
                                                <input type="number" class="form-control" id="smtpPort" value="${settings.smtp_port || 465}" required>
                                            </div>
                                            <div class="col-6 mb-3">
                                                <label class="form-label">Criptare</label>
                                                <select class="form-select" id="smtpEncryption">
                                                    <option value="ssl" ${settings.smtp_encryption === 'ssl' ? 'selected' : ''}>SSL</option>
                                                    <option value="tls" ${settings.smtp_encryption === 'tls' ? 'selected' : ''}>TLS</option>
                                                    <option value="none" ${settings.smtp_encryption === 'none' ? 'selected' : ''}>Fără</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Username SMTP *</label>
                                            <input type="text" class="form-control" id="smtpUsername" value="${escapeHtml(settings.smtp_username || '')}" placeholder="ex: admin@lentiu.ro" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Parolă SMTP ${settings.has_password ? '<small class="text-success">(configurată)</small>' : '*'}</label>
                                            <input type="password" class="form-control" id="smtpPassword" placeholder="${settings.has_password ? 'Lasă gol pentru a păstra parola curentă' : 'Introdu parola'}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h6 class="mb-0">Email Expeditor</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">Email Expeditor *</label>
                                            <input type="email" class="form-control" id="fromEmail" value="${escapeHtml(settings.from_email || '')}" placeholder="ex: admin@lentiu.ro" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Nume Expeditor</label>
                                            <input type="text" class="form-control" id="fromName" value="${escapeHtml(settings.from_name || 'Registru RE1')}" placeholder="ex: Registru RE1">
                                        </div>
                                        <div class="form-check form-switch mt-4">
                                            <input class="form-check-input" type="checkbox" id="emailIsActive" ${settings.is_active == 1 ? 'checked' : ''}>
                                            <label class="form-check-label" for="emailIsActive">Serviciu Email Activ</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Test Conexiune</h6>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted small">După salvare, poți testa conexiunea la serverul SMTP.</p>
                                        <button type="button" class="btn btn-outline-secondary" onclick="testEmailConnection()">
                                            <i class="bi bi-envelope-check"></i> Testează Conexiune
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Salvează Setări
                            </button>
                        </div>
                    </form>
                `;

                // Handler pentru submit
                document.getElementById('emailSettingsForm').addEventListener('submit', saveEmailSettings);

            } catch (error) {
                container.innerHTML = `<div class="alert alert-danger">Eroare: ${error.message}</div>`;
            }
        }

        async function saveEmailSettings(e) {
            e.preventDefault();

            const data = {
                smtp_host: document.getElementById('smtpHost').value.trim(),
                smtp_port: parseInt(document.getElementById('smtpPort').value),
                smtp_encryption: document.getElementById('smtpEncryption').value,
                smtp_username: document.getElementById('smtpUsername').value.trim(),
                smtp_password: document.getElementById('smtpPassword').value,
                from_email: document.getElementById('fromEmail').value.trim(),
                from_name: document.getElementById('fromName').value.trim(),
                is_active: document.getElementById('emailIsActive').checked ? 1 : 0
            };

            try {
                const response = await fetch('api/email_settings.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.error) {
                    alert('Eroare: ' + result.error);
                    return;
                }

                alert('Setări email salvate cu succes!');
                loadEmailSettings(); // Reload to show updated state

            } catch (error) {
                alert('Eroare: ' + error.message);
            }
        }

        async function testEmailConnection() {
            const btn = event.target.closest('button');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Se testează...';
            btn.disabled = true;

            try {
                const response = await fetch('api/test_email.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });

                const result = await response.json();

                if (result.success) {
                    let details = '';
                    if (result.details) {
                        details = '\n\nDetalii:\n- Metodă: ' + result.details.method + '\n- Server: ' + result.details.host + ':' + result.details.port + '\n- Criptare: ' + result.details.encryption;
                        if (result.details.server_response) {
                            details += '\n- Răspuns server: ' + result.details.server_response;
                        }
                    }
                    alert('Conexiune SMTP reușită!' + details);
                } else {
                    let details = '';
                    if (result.details) {
                        details = '\n\nDetalii:\n- Server: ' + result.details.host + ':' + result.details.port;
                    }
                    alert('Eroare conexiune SMTP:\n\n' + result.error + details);
                }

            } catch (error) {
                alert('Eroare la testare: ' + error.message);
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }

        // =============================================
        // EXPORT DATE
        // =============================================
        async function loadExportOptions() {
            const container = document.getElementById('export-options-container');

            // Obține statistici
            let stats = { total_entries: 0, total_manifests: 0 };
            try {
                const response = await fetch('api/manifests.php');
                const result = await response.json();
                if (result.data) {
                    stats.total_manifests = result.data.length;
                    stats.total_entries = result.data.reduce((sum, m) => sum + (m.container_count || 0), 0);
                }
            } catch (e) {}

            container.innerHTML = `
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Export Toate Datele</h5>
                                <p class="text-muted">Exportă toate înregistrările din baza de date</p>
                                <p><strong>${stats.total_entries.toLocaleString()}</strong> înregistrări în <strong>${stats.total_manifests}</strong> manifeste</p>
                                <button class="btn btn-success" onclick="exportAllData('csv')">
                                    <i class="bi bi-filetype-csv"></i> Export CSV
                                </button>
                                <button class="btn btn-primary" onclick="exportAllData('xlsx')" disabled title="Necesită PhpSpreadsheet">
                                    <i class="bi bi-file-earmark-excel"></i> Export Excel
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Export per Manifest</h5>
                                <p class="text-muted">Selectează un manifest pentru export</p>
                                <select id="exportManifestSelect" class="form-select mb-3">
                                    <option value="">Se încarcă...</option>
                                </select>
                                <button class="btn btn-success" onclick="exportManifest('csv')">
                                    <i class="bi bi-filetype-csv"></i> Export CSV
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Încarcă lista de manifeste
            try {
                const response = await fetch('api/manifests.php');
                const result = await response.json();
                const select = document.getElementById('exportManifestSelect');

                if (result.data && result.data.length > 0) {
                    select.innerHTML = '<option value="">-- Selectează Manifest --</option>' +
                        result.data.map(m => `<option value="${escapeHtml(m.manifest_number)}">${escapeHtml(m.manifest_number)} (${m.container_count} containere)</option>`).join('');
                } else {
                    select.innerHTML = '<option value="">Nu există manifeste</option>';
                }
            } catch (e) {
                document.getElementById('exportManifestSelect').innerHTML = '<option value="">Eroare la încărcare</option>';
            }
        }

        async function exportAllData(format) {
            alert('Export ' + format.toUpperCase() + ' în curs de dezvoltare.\n\nPentru moment, folosiți Export per Manifest.');
        }

        async function exportManifest(format) {
            const manifestNumber = document.getElementById('exportManifestSelect').value;
            if (!manifestNumber) {
                alert('Selectează un manifest!');
                return;
            }

            // Open in new tab for download
            window.open(`api/export.php?manifest=${encodeURIComponent(manifestNumber)}&format=${format}`, '_blank');
        }

        // =============================================
        // LOG-URI IMPORT
        // =============================================
        let logsCurrentPage = 1;

        async function loadImportLogs(page = 1) {
            logsCurrentPage = page;
            const container = document.getElementById('logs-table-container');
            container.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';

            try {
                const response = await fetch(`api/import_logs.php?page=${page}`);
                const result = await response.json();

                if (result.error) {
                    container.innerHTML = `<div class="alert alert-danger">${result.error}</div>`;
                    return;
                }

                const logs = result.data || [];
                const pagination = result.pagination || {};

                if (logs.length === 0) {
                    container.innerHTML = '<div class="alert alert-info">Nu există log-uri de import.</div>';
                    return;
                }

                let html = `
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Data</th>
                                    <th>Utilizator</th>
                                    <th>Fișier</th>
                                    <th>Importate</th>
                                    <th>Erori</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                logs.forEach(log => {
                    const createdAt = log.created_at ? formatDateTime(log.created_at) : '-';
                    const statusClass = log.rows_failed > 0 ? 'warning' : 'success';
                    const statusText = log.rows_failed > 0 ? 'Parțial' : 'Complet';

                    html += `
                        <tr>
                            <td>${log.id}</td>
                            <td>${createdAt}</td>
                            <td>${escapeHtml(log.username || '-')}</td>
                            <td>${escapeHtml(log.filename || '-')}</td>
                            <td><span class="badge bg-success">${log.rows_imported || 0}</span></td>
                            <td><span class="badge bg-danger">${log.rows_failed || 0}</span></td>
                            <td><span class="badge bg-${statusClass}">${statusText}</span></td>
                        </tr>
                    `;
                });

                html += '</tbody></table></div>';

                // Paginare
                if (pagination.total_pages > 1) {
                    html += '<nav><ul class="pagination justify-content-center">';
                    html += `<li class="page-item ${logsCurrentPage === 1 ? 'disabled' : ''}">
                        <a class="page-link" href="#" onclick="loadImportLogs(${logsCurrentPage - 1}); return false;">«</a>
                    </li>`;

                    for (let i = 1; i <= pagination.total_pages; i++) {
                        if (i === 1 || i === pagination.total_pages || Math.abs(i - logsCurrentPage) <= 2) {
                            html += `<li class="page-item ${i === logsCurrentPage ? 'active' : ''}">
                                <a class="page-link" href="#" onclick="loadImportLogs(${i}); return false;">${i}</a>
                            </li>`;
                        } else if (Math.abs(i - logsCurrentPage) === 3) {
                            html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                    }

                    html += `<li class="page-item ${logsCurrentPage === pagination.total_pages ? 'disabled' : ''}">
                        <a class="page-link" href="#" onclick="loadImportLogs(${logsCurrentPage + 1}); return false;">»</a>
                    </li>`;
                    html += '</ul></nav>';
                }

                container.innerHTML = html;

            } catch (error) {
                container.innerHTML = `<div class="alert alert-danger">Eroare: ${error.message}</div>`;
            }
        }
    </script>
</body>
</html>
