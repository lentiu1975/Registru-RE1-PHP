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
$currentUser = dbFetchOne("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
if (!$currentUser) {
    session_destroy();
    header('Location: login.php');
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

        // Placeholder functions (to be implemented)
        function loadUsers() {
            document.getElementById('users-table-container').innerHTML = '<p class="text-muted">Funcționalitate în curs de implementare...</p>';
        }

        function loadDatabaseYears() {
            document.getElementById('years-table-container').innerHTML = '<p class="text-muted">Funcționalitate în curs de implementare...</p>';
        }

        function loadPavilions() {
            document.getElementById('pavilions-table-container').innerHTML = '<p class="text-muted">Funcționalitate în curs de implementare...</p>';
        }

        function loadContainerTypes() {
            document.getElementById('container-types-table-container').innerHTML = '<p class="text-muted">Funcționalitate în curs de implementare...</p>';
        }

        function loadImportTemplates() {
            document.getElementById('templates-table-container').innerHTML = '<p class="text-muted">Funcționalitate în curs de implementare...</p>';
        }

        function loadExportOptions() {
            document.getElementById('export-options-container').innerHTML = '<p class="text-muted">Funcționalitate în curs de implementare...</p>';
        }

        function loadImportLogs() {
            document.getElementById('logs-table-container').innerHTML = '<p class="text-muted">Funcționalitate în curs de implementare...</p>';
        }

        // Modal functions (placeholders)
        function showUserModal() {
            alert('Modal utilizator - în curs de implementare');
        }

        function showYearModal() {
            alert('Modal an - în curs de implementare');
        }

        function showPavilionModal() {
            alert('Modal pavilion - în curs de implementare');
        }

        function showContainerTypeModal() {
            alert('Modal tip container - în curs de implementare');
        }

        function showTemplateModal() {
            alert('Modal template - în curs de implementare');
        }
    </script>
</body>
</html>
